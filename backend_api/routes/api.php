<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\OffenseController;
use App\Http\Controllers\Api\SubjectController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/priority-subjects', [DashboardController::class, 'prioritySubjects']);
    Route::get('/bulletins/current', [DashboardController::class, 'bulletin']);
    Route::get('/subjects/export', [SubjectController::class, 'export']);
    Route::get('/subjects', [SubjectController::class, 'index']);
    Route::post('/subjects', [SubjectController::class, 'store']);
    Route::post('/subjects/{subject}/restore', [SubjectController::class, 'restore'])->whereNumber('subject');
    Route::get('/subjects/{subject}', [SubjectController::class, 'show']);
    Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
    Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);
    Route::get('/subjects/{subject}/offenses', [OffenseController::class, 'forSubject']);
    Route::post('/subjects/{subject}/offenses', [OffenseController::class, 'store']);
    Route::get('/subjects/{subject}/notes', [NoteController::class, 'index']);
    Route::post('/subjects/{subject}/notes', [NoteController::class, 'store']);
    Route::delete('/notes/{note}', [NoteController::class, 'destroy']);
    Route::get('/offenses', [OffenseController::class, 'index']);
    Route::delete('/offenses/{offense}', [OffenseController::class, 'destroy']);
    Route::get('/crime-categories', [OffenseController::class, 'categories']);
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
});
