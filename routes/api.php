<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperationsController;
use Illuminate\Support\Facades\Route;
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/login', [ApiController::class, 'login'])->middleware('throttle:5,1');
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::get('/sync/bootstrap', [\App\Http\Controllers\SyncController::class, 'bootstrap']);
        Route::get('/sync/pull', [\App\Http\Controllers\SyncController::class, 'pull']);
        Route::post('/sync/push', [\App\Http\Controllers\SyncController::class, 'push']);
        Route::post('/sync/acknowledge', [\App\Http\Controllers\SyncController::class, 'acknowledge']);
        Route::get('/sync/status/{batch}', [\App\Http\Controllers\SyncController::class, 'status']);
        Route::post('/logout', [ApiController::class, 'logout']);
        Route::get('/me', [ApiController::class, 'me']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/equipment', [ApiController::class, 'equipment']);
        Route::get('/assignments', [ApiController::class, 'assignments']);
        Route::get('/master-data', [ApiController::class, 'master']);
        Route::get('/notifications', [DashboardController::class, 'notifications']);
        Route::apiResource('/daily-reports', DailyReportController::class)->only(['index','show']);
        Route::post('/daily-reports', [DailyReportController::class,'store'])->middleware(\App\Http\Middleware\IdempotentApiMutation::class);
        Route::put('/daily-reports/{dailyReport}', [DailyReportController::class,'update'])->middleware(\App\Http\Middleware\IdempotentApiMutation::class);
        Route::get('/attachments/{attachment}', [DailyReportController::class,'download']);
        Route::post('/daily-reports/{dailyReport}/submit', [DailyReportController::class, 'transition'])->defaults('action', 'submit')->middleware(\App\Http\Middleware\IdempotentApiMutation::class);
        Route::post('/daily-reports/{dailyReport}/attachments', [DailyReportController::class, 'attachment'])->middleware(\App\Http\Middleware\IdempotentApiMutation::class);
        Route::post('/breakdowns', [OperationsController::class, 'breakdown'])->middleware(\App\Http\Middleware\IdempotentApiMutation::class);
    });
});
