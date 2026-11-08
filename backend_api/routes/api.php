<?php

use App\Http\Controllers\Api\AccessRequestController;
use App\Http\Controllers\Api\Admin\AccessReviewController;
use App\Http\Controllers\Api\Admin\ArchivedSubjectController;
use App\Http\Controllers\Api\Admin\PersonnelController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaseFileController;
use App\Http\Controllers\Api\CostRateController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InfractionController;
use App\Http\Controllers\Api\InfractionTypeController;
use App\Http\Controllers\Api\ManhuntController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SightingController;
use App\Http\Controllers\Api\SubjectAnalyticsController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\SubjectPhotoController;
use App\Http\Controllers\Api\SubjectReportController;
use App\Http\Controllers\Api\SubjectStatusController;
use Illuminate\Support\Facades\Route;

/*
| Clearance levels. Each level includes everything below it. The rank decides the highest level a person may hold.
|   1 Patrol Officer   files field reports, edits their own, sees no records of people, deletes nothing
|   2 Senior Officer   reads the database, adds field records, reviews and edits reports
|   3 Detective        creates and edits records, changes status, marks infractions served, case files, photos
|   4 Supervisor       archives records, exports, threat override, cost rates, audit log
|   5 Chief Admin      personnel and access requests, corrects crime history, clears and restores records
*/

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Portraits. An <img> tag cannot send a login token, so the link is signed and expires.
    Route::get('/subjects/{subject}/photo', [SubjectPhotoController::class, 'show'])->middleware('signed')->name('subjects.photo');

    // Public: real Bohol places, and the application for access.
    Route::middleware('throttle:places')->group(function () {
        Route::get('/places/municipalities', [PlaceController::class, 'municipalities']);
        Route::get('/places/municipalities/{municipality}/barangays', [PlaceController::class, 'barangays']);
        Route::get('/access-requests/options', [AccessRequestController::class, 'options']);
    });
    Route::post('/access-requests', [AccessRequestController::class, 'store'])->middleware('throttle:access-request');
    Route::get('/access-requests/{reference}', [AccessRequestController::class, 'show'])
        ->where('reference', 'ACC-[A-Z0-9]{10}')
        ->middleware('throttle:access-status');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Level 1: file reports. No records of people.
        Route::middleware('clearance:1')->group(function () {
            Route::get('/auth/me', [AuthController::class, 'me']);

            Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
            Route::get('/bulletins/current', [DashboardController::class, 'bulletin']);
            Route::get('/infraction-types', [InfractionTypeController::class, 'index']);

            Route::get('/reports', [ReportController::class, 'index']);
            Route::post('/reports', [ReportController::class, 'store']);
            Route::get('/reports/{report}', [ReportController::class, 'show']);
            Route::put('/reports/{report}', [ReportController::class, 'update']);
        });

        // Level 2: the database of people, field records, report review.
        Route::middleware('clearance:2')->group(function () {
            Route::get('/dashboard/priority', [DashboardController::class, 'priority']);

            Route::get('/subjects', [SubjectController::class, 'index']);
            Route::get('/subjects/{subject}', [SubjectController::class, 'show']);
            Route::get('/subjects/{subject}/rap-sheet', [SubjectReportController::class, 'rapSheet']);
            Route::get('/subjects/{subject}/infraction-summary', [SubjectReportController::class, 'infractionSummary']);
            Route::get('/subjects/{subject}/cost-to-state', [SubjectReportController::class, 'costToState']);
            Route::get('/subjects/{subject}/locations', [SubjectReportController::class, 'locations']);
            Route::get('/subjects/{subject}/analytics', [SubjectAnalyticsController::class, 'show']);
            Route::get('/subjects/{subject}/status-history', [SubjectStatusController::class, 'index']);
            Route::get('/subjects/{subject}/infractions', [InfractionController::class, 'forSubject']);
            Route::get('/subjects/{subject}/manhunts', [ManhuntController::class, 'forSubject']);
            Route::get('/subjects/{subject}/notes', [NoteController::class, 'index']);

            Route::get('/infractions', [InfractionController::class, 'index']);
            Route::get('/manhunts', [ManhuntController::class, 'index']);
            Route::get('/manhunts/{manhunt}', [ManhuntController::class, 'show']);
            Route::get('/rankings', [RankingController::class, 'index']);
            Route::get('/cost-rates', [CostRateController::class, 'index']);

            Route::post('/subjects/{subject}/infractions', [InfractionController::class, 'store']);
            Route::post('/subjects/{subject}/manhunts', [ManhuntController::class, 'store']);
            Route::post('/subjects/{subject}/sightings', [SightingController::class, 'store']);
            Route::post('/subjects/{subject}/notes', [NoteController::class, 'store']);
            Route::delete('/notes/{note}', [NoteController::class, 'destroy']);

            Route::post('/reports/{report}/review', [ReportController::class, 'review']);
        });

        // Level 3: records.
        Route::middleware('clearance:3')->group(function () {
            Route::post('/subjects', [SubjectController::class, 'store']);
            Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
            Route::post('/subjects/{subject}/status', [SubjectStatusController::class, 'store']);
            Route::post('/subjects/{subject}/case-file', [CaseFileController::class, 'show']);
            Route::post('/subjects/{subject}/photo', [SubjectPhotoController::class, 'store']);
            Route::delete('/subjects/{subject}/photo', [SubjectPhotoController::class, 'destroy']);
            Route::patch('/infractions/{infraction}', [InfractionController::class, 'update']);
        });

        // Level 4: supervisor.
        Route::middleware('clearance:4')->group(function () {
            Route::get('/subjects/export', [SubjectController::class, 'export']);
            Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);
            Route::put('/subjects/{subject}/threat-override', [SubjectStatusController::class, 'threat']);
            Route::put('/cost-rates', [CostRateController::class, 'update']);
            Route::get('/audit-logs', [AuditLogController::class, 'index']);
        });

        // Level 5: Chief Admin. Crime history corrections, restoring records, and everything about personnel.
        Route::middleware('clearance:5')->group(function () {
            Route::post('/subjects/{subject}/restore', [SubjectController::class, 'restore'])->withTrashed();
            Route::delete('/infractions/{infraction}', [InfractionController::class, 'destroy']);
            Route::delete('/manhunts/{manhunt}', [ManhuntController::class, 'destroy']);
            Route::delete('/reports/{report}', [ReportController::class, 'destroy']);

            Route::prefix('admin')->group(function () {
                Route::get('/summary', [AccessReviewController::class, 'summary']);
                Route::get('/access-requests', [AccessReviewController::class, 'index']);
                Route::post('/access-requests/{accessRequest}/approve', [AccessReviewController::class, 'approve']);
                Route::post('/access-requests/{accessRequest}/reject', [AccessReviewController::class, 'reject']);

                Route::get('/personnel', [PersonnelController::class, 'index']);
                Route::post('/personnel', [PersonnelController::class, 'store']);
                Route::put('/personnel/{user}', [PersonnelController::class, 'update']);
                Route::post('/personnel/{user}/password', [PersonnelController::class, 'password']);
                Route::post('/personnel/{user}/activate', [PersonnelController::class, 'activate']);
                Route::post('/personnel/{user}/deactivate', [PersonnelController::class, 'deactivate']);

                Route::get('/archived-subjects', [ArchivedSubjectController::class, 'index']);
            });
        });
    });
});
