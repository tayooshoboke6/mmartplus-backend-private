<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| API Test Routes
|--------------------------------------------------------------------------
|
| Here are routes for testing CORS and other functionality
|
*/

Route::get('/cors-test', function (Request $request) {
    Log::info('CORS Test endpoint hit', [
        'headers' => $request->headers->all(),
        'origin' => $request->header('Origin'),
        'method' => $request->method()
    ]);
    
    return response()->json([
        'status' => 'success',
        'message' => 'CORS test successful',
        'origin' => $request->header('Origin'),
        'received_headers' => [
            'Origin' => $request->header('Origin'),
            'Host' => $request->header('Host'),
            'User-Agent' => $request->header('User-Agent'),
            'Content-Type' => $request->header('Content-Type'),
        ],
        'server_time' => now()->toIso8601String(),
    ]);
});
