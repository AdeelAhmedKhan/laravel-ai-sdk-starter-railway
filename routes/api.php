<?php

use App\Http\Controllers\Api\AiDemoController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Laravel AI SDK starter
|--------------------------------------------------------------------------
|
| Health is public (Railway healthchecks it). The AI demo is public
| too — it is a demo with no user data — but it only calls a provider
| when a provider key is configured; otherwise it says so plainly.
|
*/

// Public: Railway healthcheck + honest status. No auth, no throttle
// beyond the default `api` group limit (60/min).
Route::get('/health', [HealthController::class, 'show']);

// Demo: one AI call behind a provider key (optional at deploy).
Route::get('/ai/demo', [AiDemoController::class, 'show']);
Route::post('/ai/demo', [AiDemoController::class, 'prompt'])
    ->middleware('throttle:10,1');
