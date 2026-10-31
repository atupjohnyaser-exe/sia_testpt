<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaseFileController;
use App\Http\Controllers\Api\CostRateController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InfractionController;
use App\Http\Controllers\Api\InfractionTypeController;
use App\Http\Controllers\Api\ManhuntController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Api\SightingController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\SubjectReportController;
use App\Http\Controllers\Api\SubjectStatusController;
use Illuminate\Support\Facades\Route;

/*
| Clearance levels
|   1 Records Clerk  read only
|   2 Officer        add infractions, manhunts, sightings and notes
|   3 Detective      create and edit records, change status, mark infractions served, case files
|   4 Supervisor     archive and restore, export, delete, threat override, cost rates, audit log
*/

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Level 1: read
        Route::middleware('clearance:1')->group(function () {
            Route::get('/auth/me', [AuthController::class, 'me']);

            Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
            Route::get('/dashboard/priority', [DashboardController::class, 'priority']);
            Route::get('/bulletins/current', [DashboardController::class, 'bulletin']);

            Route::get('/subjects', [SubjectController::class, 'index']);
            Route::get('/subjects/{subject}', [SubjectController::class, 'show']);
            Route::get('/subjects/{subject}/rap-sheet', [SubjectReportController::class, 'rapSheet']);
            Route::get('/subjects/{subject}/infraction-summary', [SubjectReportController::class, 'infractionSummary']);
            Route::get('/subjects/{subject}/cost-to-state', [SubjectReportController::class, 'costToState']);
            Route::get('/subjects/{subject}/locations', [SubjectReportController::class, 'locations']);
            Route::get('/subjects/{subject}/status-history', [SubjectStatusController::class, 'index']);
            Route::get('/subjects/{subject}/infractions', [InfractionController::class, 'forSubject']);
            Route::get('/subjects/{subject}/manhunts', [ManhuntController::class, 'forSubject']);
            Route::get('/subjects/{subject}/notes', [NoteController::class, 'index']);

            Route::get('/infractions', [InfractionController::class, 'index']);
            Route::get('/infraction-types', [InfractionTypeController::class, 'index']);
            Route::get('/manhunts', [ManhuntController::class, 'index']);
            Route::get('/manhunts/{manhunt}', [ManhuntController::class, 'show']);
            Route::get('/rankings', [RankingController::class, 'index']);
            Route::get('/cost-rates', [CostRateController::class, 'index']);
        });

        // Level 2: field work
        Route::middleware('clearance:2')->group(function () {
            Route::post('/subjects/{subject}/infractions', [InfractionController::class, 'store']);
            Route::post('/subjects/{subject}/manhunts', [ManhuntController::class, 'store']);
            Route::post('/subjects/{subject}/sightings', [SightingController::class, 'store']);
            Route::post('/subjects/{subject}/notes', [NoteController::class, 'store']);
            Route::delete('/notes/{note}', [NoteController::class, 'destroy']);
        });

        // Level 3: records
        Route::middleware('clearance:3')->group(function () {
            Route::post('/subjects', [SubjectController::class, 'store']);
            Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
            Route::post('/subjects/{subject}/status', [SubjectStatusController::class, 'store']);
            Route::post('/subjects/{subject}/case-file', [CaseFileController::class, 'show']);
            Route::patch('/infractions/{infraction}', [InfractionController::class, 'update']);
        });

        // Level 4: supervisor
        Route::middleware('clearance:4')->group(function () {
            Route::get('/subjects/export', [SubjectController::class, 'export']);
            Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);
            Route::post('/subjects/{subject}/restore', [SubjectController::class, 'restore'])->withTrashed();
            Route::put('/subjects/{subject}/threat-override', [SubjectStatusController::class, 'threat']);
            Route::delete('/infractions/{infraction}', [InfractionController::class, 'destroy']);
            Route::delete('/manhunts/{manhunt}', [ManhuntController::class, 'destroy']);
            Route::put('/cost-rates', [CostRateController::class, 'update']);
            Route::get('/audit-logs', [AuditLogController::class, 'index']);
        });
    });
});
