<?php

return [

    // Token auth only, so no stateful (cookie) domains.
    'stateful' => array_filter(explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', ''))),

    'guard' => ['web'],

    // Minutes. Tokens stop working after this, even if they were never revoked.
    'expiration' => (int) env('TOKEN_TTL_MINUTES', 480),

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
];
