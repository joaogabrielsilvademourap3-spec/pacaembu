<?php

use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContentCalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\MetricController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WebsiteProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('clients', ClientController::class);
    Route::resource('projects', ProjectController::class);
    Route::resource('tasks', TaskController::class);
    Route::resource('content-calendar', ContentCalendarController::class);
    Route::resource('website-projects', WebsiteProjectController::class);
    Route::resource('metrics', MetricController::class);
    Route::resource('reports', ReportController::class);
    Route::resource('payments', PaymentController::class);
    Route::resource('files', FileController::class);
    Route::resource('approvals', ApprovalController::class);
    Route::get('ai-assistant', [AiAssistantController::class, 'index'])->name('ai-assistant.index');
    Route::post('ai-assistant', [AiAssistantController::class, 'store'])->name('ai-assistant.store');
});

Route::get('/public/approvals/{token}', [ApprovalController::class, 'publicShow'])->name('approvals.public.show');
Route::post('/public/approvals/{token}', [ApprovalController::class, 'publicUpdate'])->name('approvals.public.update');
