<?php

return [

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
];
