<?php

return [

    'name' => 'Bohol Police Department',

    // Badge IDs look like BPD-4821. Digits are random, never chosen by the applicant.
    'badge_prefix' => 'BPD',

    /*
    | Demo mode lets an unknown badge ID create its own low-clearance account.
    | It never skips the password check for an officer that already exists.
    | Keep it off anywhere real people can reach the API.
    */
    'demo_mode' => (bool) env('APP_DEMO_MODE', false),
    'demo_clearance' => (int) env('DEMO_DEFAULT_CLEARANCE', 2),

    'token_ttl_minutes' => (int) env('TOKEN_TTL_MINUTES', 480),

    // Used only by the officer seeder.
    'seed_password' => env('SEED_OFFICER_PASSWORD'),

    // A subject view is written to the audit log once per officer in this window.
    'view_log_minutes' => 10,

    // Manhunt bounty = cost to state of that manhunt x this number.
    'manhunt_bounty_multiplier' => 0.4,

    // Minimum bounty for each threat level. Keep ascending.
    'threat_thresholds' => [
        1 => 0,
        2 => 10_000,
        3 => 100_000,
        4 => 500_000,
        5 => 2_000_000,
        6 => 10_000_000,
    ],

    /*
    | Ranks and the highest clearance each rank may hold. The higher the rank, the higher the level.
    |   level 1 Patrol Officer   files reports, sees no people
    |   level 2 Senior Officer   reads and edits most of the database
    |   level 3 Detective        creates and edits records, status, case files
    |   level 4 Supervisor       archives, exports, audit log, threat override
    |   level 5 Chief Admin      personnel, crime history corrections, clearing records
    |
    | 'auto' means the level is set on the application without a decision (Detectives get level 3).
    | The Chief Admin still reviews every application. 'apply' => false hides a rank from the form.
    */
    'ranks' => [
        ['name' => 'Patrolman / Patrolwoman', 'level' => 1],
        ['name' => 'Police Corporal', 'level' => 1],
        ['name' => 'Police Staff Sergeant', 'level' => 2],
        ['name' => 'Police Master Sergeant', 'level' => 2],
        ['name' => 'Police Senior Master Sergeant', 'level' => 2],
        ['name' => 'Police Chief Master Sergeant', 'level' => 2],
        ['name' => 'Police Executive Master Sergeant', 'level' => 2],
        ['name' => 'Police Lieutenant', 'level' => 3],
        ['name' => 'Detective', 'level' => 3, 'auto' => true],
        ['name' => 'Police Captain', 'level' => 4],
        ['name' => 'Police Major', 'level' => 4],
        ['name' => 'Police Lieutenant Colonel', 'level' => 4],
        ['name' => 'Police Colonel', 'level' => 4],
        ['name' => 'Provincial Director', 'level' => 5, 'apply' => false],
    ],

    // Application form limits.
    'application' => [
        'min_age' => 21,
        'max_age' => 65,
        // Applications allowed per hour from one address. The form is open to anyone, so this stays low.
        'per_hour' => 5,
    ],

    // Places come from PSGC Cloud (https://psgc.cloud). Run: php artisan bpd:sync-places
    'psgc' => [
        'base_url' => env('PSGC_BASE_URL', 'https://psgc.cloud/api/v2'),
        // A province name or its code.
        'province' => env('PSGC_PROVINCE', 'Bohol'),
        // Tagbilaran, the capital, is a city and not one of the 47 municipalities.
        'include_cities' => (bool) env('PSGC_INCLUDE_CITIES', true),
        'timeout' => 20,
        'retry_ms' => 1500,
    ],
];
