<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\BackgroundTaskController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SapController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
Route::get('/', function () {
    return redirect(auth()->check() ? '/dashboard' : '/login');
});
Auth::routes(['register' => false, 'verify' => false]);
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/background-tasks', [BackgroundTaskController::class, 'index']);
    Route::post('/background-tasks/export', [BackgroundTaskController::class, 'export']);
    Route::get('/background-tasks/{task}/download', [BackgroundTaskController::class, 'download']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/select-plant', [DashboardController::class, 'plants']);
    Route::post('/select-plant', [DashboardController::class, 'select']);
    Route::get('/profile', [DashboardController::class, 'profile']);
    Route::put('/profile', [DashboardController::class, 'updateProfile']);
    Route::get('/notifications', [DashboardController::class, 'notifications']);
    Route::post('/notifications/{id}/read', [DashboardController::class, 'readNotification']);
    Route::get('/master/{module}', [MasterController::class, 'index']);
    Route::get('/master/{module}/create', [MasterController::class, 'form']);
    Route::get('/master/{module}/{id}/edit', [MasterController::class, 'form']);
    Route::post('/master/{module}', [MasterController::class, 'save']);
    Route::put('/master/{module}/{id}', [MasterController::class, 'save']);
    Route::delete('/master/{module}/{id}', [MasterController::class, 'destroy']);
    Route::get('/approvals', [DailyReportController::class, 'approvals']);
    Route::resource('/daily-reports', DailyReportController::class)->except('destroy');
    Route::post('/daily-reports/{dailyReport}/attachments', [DailyReportController::class, 'attachment']);
    Route::post('/daily-reports/{dailyReport}/{action}', [DailyReportController::class, 'transition'])->where('action', 'submit|return|verify|approve|revise');
    Route::get('/attachments/{attachment}', [DailyReportController::class, 'download']);
    foreach (['work-plans', 'assignments', 'breakdowns'] as $module) {
        Route::get('/' . $module, [OperationsController::class, 'index'])->defaults('module', $module);
    }
    Route::get('/maintenance/work-orders', [OperationsController::class, 'index'])->defaults('module', 'work-orders');
    Route::post('/work-plans', [OperationsController::class, 'plan']);
    Route::post('/assignments', [OperationsController::class, 'assignment']);
    Route::post('/assignments/{assignment}/cancel', [OperationsController::class, 'cancelAssignment']);
    Route::post('/breakdowns', [OperationsController::class, 'breakdown']);
    Route::post('/maintenance/work-orders/{order}/attachments', [OperationsController::class, 'attachment']);
    Route::get('/maintenance/attachments/{attachment}', [OperationsController::class, 'downloadAttachment']);
    Route::post('/maintenance/work-orders', [OperationsController::class, 'workOrder']);
    Route::get('/maintenance/work-orders/{order}', [OperationsController::class, 'showOrder']);
    Route::post('/maintenance/work-orders/{order}/actions', [OperationsController::class, 'maintenanceAction']);
    Route::post('/maintenance/work-orders/{order}/{action}', [OperationsController::class, 'transition'])->where('action', 'start|complete|close');
    Route::get('/sap/imports', [SapController::class, 'index']);
    Route::get('/sap/template', [SapController::class, 'template']);
    Route::post('/sap/imports', [SapController::class, 'store']);
    Route::get('/sap/imports/{batch}', [SapController::class, 'show']);
    Route::post('/sap/imports/{batch}/{action}', [SapController::class, 'action'])->where('action', 'commit|rollback|reconcile');
    Route::get('/sap/reconciliation', [SapController::class, 'reconciliation']);
    Route::post('/sap/mappings', [SapController::class, 'mapping']);
    Route::post('/sap/reconciliation/{reconciliation}/review', [SapController::class, 'review']);
    Route::get('/reports/{type?}', [ReportController::class, 'index']);
    Route::get('/audit-logs', [ReportController::class, 'audit']);
    Route::get('/users', [AccessController::class, 'index']);
    Route::post('/users', [AccessController::class, 'save']);
    Route::post('/users/{user}/toggle', [AccessController::class, 'toggle']);
    Route::get('/roles', [AccessController::class, 'roles']);
});
