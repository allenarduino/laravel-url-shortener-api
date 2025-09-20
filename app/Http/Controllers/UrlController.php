<?php

namespace App\Http\Controllers;

use App\Jobs\IncrementClickJob;
use App\Models\Metric;
use App\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UrlController extends Controller
{
    // POST /api/shorten
    public function shorten(Request $request)
    {
        $startTime = microtime(true);
        
        $data = $request->validate([
            'original_url' => [
                'required',
                'url',
                'max:2048',
                'regex:/^https?:\/\//i', // Only allow http/https protocols
            ],
            'custom_code'  => [
                'nullable', 
                'alpha_num', 
                'min:4', 
                'max:12', 
                'regex:/^[A-Za-z0-9]+$/', // Strict alphanumeric only
                Rule::unique('urls','short_code')
            ],
            'expires_at'   => 'nullable|date|after:now',
        ]);

        // Log the request for security monitoring
        Log::info('URL Shortening Request', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'original_url' => $data['original_url'],
            'custom_code' => $data['custom_code'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        // Track metrics
        Metric::counter('url_shorten_requests', 1, [
            'ip' => $request->ip(),
            'has_custom_code' => !is_null($data['custom_code'] ?? null),
        ]);

        $shortCode = $data['custom_code'] ?? Url::generateUniqueShortCode();

        $url = Url::create([
            'original_url' => $data['original_url'],
            'short_code' => $shortCode,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        // Log successful creation
        Log::info('URL Created Successfully', [
            'url_id' => $url->id,
            'short_code' => $url->short_code,
            'ip' => $request->ip(),
        ]);

        // Track response time
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        Metric::histogram('url_shorten_response_time', $responseTime, [
            'endpoint' => 'shorten',
        ]);

        return response()->json([
            'short_code' => $url->short_code,
            'short_url' => url($url->short_code),
            'original_url' => $url->original_url,
        ], 201);
    }

    // GET /{code}  (web route: redirect)
    public function redirect($code, Request $request)
    {
        $startTime = microtime(true);
        
        // Log redirect attempts for security monitoring
        Log::info('URL Redirect Attempt', [
            'short_code' => $code,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
        ]);

        // Track redirect attempts
        Metric::counter('url_redirect_attempts', 1, [
            'short_code' => $code,
            'ip' => $request->ip(),
        ]);

        $cacheKey = "short_url:{$code}";

        // try cache first
        $cached = Cache::get($cacheKey);
        if ($cached) {
            // Log cached redirect
            Log::info('Cached Redirect', [
                'short_code' => $code,
                'original_url' => $cached['original_url'],
                'ip' => $request->ip(),
            ]);

            // Track cache hit
            Metric::counter('url_redirect_cache_hits', 1, [
                'short_code' => $code,
            ]);

            // dispatch click increment in background
            if (isset($cached['id'])) {
                dispatch(new IncrementClickJob($cached['id']));
            }
            
            // Track response time for cached redirects
            $responseTime = (microtime(true) - $startTime) * 1000;
            Metric::histogram('url_redirect_response_time', $responseTime, [
                'endpoint' => 'redirect',
                'cache_hit' => true,
            ]);
            
            return redirect()->away($cached['original_url']);
        }

        $url = Url::where('short_code', $code)->firstOrFail();

        if ($url->expires_at && now()->greaterThan($url->expires_at)) {
            Log::warning('Expired URL Access Attempt', [
                'short_code' => $code,
                'expires_at' => $url->expires_at,
                'ip' => $request->ip(),
            ]);
            
            // Track expired URL attempts
            Metric::counter('url_redirect_expired_attempts', 1, [
                'short_code' => $code,
                'ip' => $request->ip(),
            ]);
            
            abort(410, 'This link has expired.');
        }

        // Log successful redirect
        Log::info('URL Redirect Success', [
            'short_code' => $code,
            'original_url' => $url->original_url,
            'ip' => $request->ip(),
        ]);

        // Track successful redirects
        Metric::counter('url_redirect_success', 1, [
            'short_code' => $code,
            'ip' => $request->ip(),
        ]);

        // cache for 60 minutes
        Cache::put($cacheKey, [
            'id' => $url->id,
            'original_url' => $url->original_url
        ], now()->addMinutes(60));

        // increment clicks via queue
        dispatch(new IncrementClickJob($url->id));

        // Track response time for database redirects
        $responseTime = (microtime(true) - $startTime) * 1000;
        Metric::histogram('url_redirect_response_time', $responseTime, [
            'endpoint' => 'redirect',
            'cache_hit' => false,
        ]);

        return redirect()->away($url->original_url);
    }

    // GET /api/{code}/stats
    public function stats($code)
    {
        $url = Url::where('short_code', $code)->firstOrFail();

        return response()->json([
            'original_url' => $url->original_url,
            'clicks' => $url->clicks,
            'created_at' => $url->created_at,
            'expires_at' => $url->expires_at,
        ]);
    }
}
