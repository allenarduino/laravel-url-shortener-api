<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HmacAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');

        // Check if all required headers are present
        if (!$apiKey || !$signature || !$timestamp) {
            Log::warning('HMAC Auth Failed - Missing Headers', [
                'ip' => $request->ip(),
                'has_api_key' => !is_null($apiKey),
                'has_signature' => !is_null($signature),
                'has_timestamp' => !is_null($timestamp),
            ]);
            
            return response()->json(['error' => 'Missing authentication headers'], 401);
        }

        // Check timestamp (prevent replay attacks)
        $currentTime = time();
        $requestTime = (int) $timestamp;
        
        if (abs($currentTime - $requestTime) > 300) { // 5 minutes tolerance
            Log::warning('HMAC Auth Failed - Timestamp Expired', [
                'ip' => $request->ip(),
                'current_time' => $currentTime,
                'request_time' => $requestTime,
                'difference' => abs($currentTime - $requestTime),
            ]);
            
            return response()->json(['error' => 'Request timestamp expired'], 401);
        }

        // Get the secret key for this API key (in production, store in database)
        $secretKey = config('app.hmac_secret_key');
        
        if (!$secretKey) {
            Log::error('HMAC Secret Key Not Configured');
            return response()->json(['error' => 'Server configuration error'], 500);
        }

        // Create the signature
        $method = $request->method();
        $path = $request->path();
        $body = $request->getContent();
        
        $stringToSign = $method . "\n" . $path . "\n" . $body . "\n" . $timestamp;
        $expectedSignature = hash_hmac('sha256', $stringToSign, $secretKey);

        // Verify signature
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('HMAC Auth Failed - Invalid Signature', [
                'ip' => $request->ip(),
                'api_key' => $apiKey,
                'expected_signature' => $expectedSignature,
                'provided_signature' => $signature,
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        Log::info('HMAC Auth Success', [
            'ip' => $request->ip(),
            'api_key' => $apiKey,
        ]);

        return $next($request);
    }
}
