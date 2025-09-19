<?php

namespace App\Http\Controllers;

use App\Jobs\IncrementClickJob;
use App\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UrlController extends Controller
{
    // POST /api/shorten
    public function shorten(Request $request)
    {
        $data = $request->validate([
            'original_url' => 'required|url|max:2048',
            'custom_code'  => ['nullable', 'alpha_num', 'min:4', 'max:12', Rule::unique('urls','short_code')],
            'expires_at'   => 'nullable|date',
        ]);

        $shortCode = $data['custom_code'] ?? Url::generateUniqueShortCode();

        $url = Url::create([
            'original_url' => $data['original_url'],
            'short_code' => $shortCode,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return response()->json([
            'short_code' => $url->short_code,
            'short_url' => url($url->short_code),
            'original_url' => $url->original_url,
        ], 201);
    }

    // GET /{code}  (web route: redirect)
    public function redirect($code)
    {
        $cacheKey = "short_url:{$code}";

        // try cache first
        $cached = Cache::get($cacheKey);
        if ($cached) {
            // dispatch click increment in background
            if (isset($cached['id'])) {
                IncrementClickJob::dispatch($cached['id']);
            }
            return redirect()->away($cached['original_url']);
        }

        $url = Url::where('short_code', $code)->firstOrFail();

        if ($url->expires_at && now()->greaterThan($url->expires_at)) {
            abort(410, 'This link has expired.');
        }

        // cache for 60 minutes
        Cache::put($cacheKey, [
            'id' => $url->id,
            'original_url' => $url->original_url
        ], now()->addMinutes(60));

        // increment clicks via queue
        IncrementClickJob::dispatch($url->id);

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
