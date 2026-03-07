<?php

use Illuminate\Support\Facades\Route;

'Route::get('/', function () {
    return response()->json([
        'message' => 'Backend is running',
        'api_base' => url('/api'),
        'api_status' => url('/api/status'),
    ]);
});'