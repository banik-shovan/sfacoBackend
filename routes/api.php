<?php

use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AlumniContentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\MemberProfileController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/status', function (Request $request) {
    return response()->json([
        'app' => config('app.name'),
        'status' => 'ok',
        'version' => app()->version(),
        'timestamp' => now()->toISOString(),
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register/verify-code', [AuthController::class, 'verifyRegistrationCode']);
    Route::post('/register/resend-code', [AuthController::class, 'resendRegistrationCode']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::get('/site-settings', [SiteSettingController::class, 'show']);
Route::get('/alumni-content', [AlumniContentController::class, 'show']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/public-stats', function () {
    return response()->json([
        'member_count' => User::query()->where('role', 'member')->count(),
    ]);
});

Route::middleware(['auth:sanctum', 'active.user'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'active.user'])->group(function () {
    Route::get('/member-profile/schema', [MemberProfileController::class, 'schema']);
});

Route::middleware(['auth:sanctum', 'active.user', 'role:member'])
    ->prefix('member')
    ->group(function () {
        Route::get('/profile', [MemberProfileController::class, 'show']);
        Route::post('/profile', [MemberProfileController::class, 'upsert']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

Route::middleware(['auth:sanctum', 'active.user', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/members', [AdminUserController::class, 'indexMembers']);
        Route::get('/members/{user}/profile', [AdminUserController::class, 'showMemberProfile']);
        Route::post('/members/{user}/profile', [AdminUserController::class, 'updateMemberProfile']);
        Route::post('/site-settings', [SiteSettingController::class, 'upsert']);
        Route::post('/alumni-content', [AlumniContentController::class, 'upsert']);
        Route::post('/alumni-content/upload-banner', [AlumniContentController::class, 'uploadBanner']);
        Route::get('/events', [EventController::class, 'adminIndex']);
        Route::post('/events', [EventController::class, 'store']);
        Route::patch('/events/{event}', [EventController::class, 'update']);
        Route::delete('/events/{event}', [EventController::class, 'destroy']);
        Route::patch('/members/{user}/approve', [AdminUserController::class, 'approveMember']);
        Route::patch('/members/{user}/deactivate', [AdminUserController::class, 'deactivateUser']);
        Route::patch('/members/{user}/activate', [AdminUserController::class, 'activateUser']);
        Route::patch('/users/{user}/role', [AdminUserController::class, 'setRole']);
    });
