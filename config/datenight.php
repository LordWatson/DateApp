<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Challenge Configuration
    |--------------------------------------------------------------------------
    */
    'challenge_frequency' => env('CHALLENGE_FREQUENCY', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Invitation Configuration
    |--------------------------------------------------------------------------
    */
    'invitation_expiry_days' => env('INVITATION_EXPIRY_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Compatibility Thresholds
    |--------------------------------------------------------------------------
    */
    'compatibility' => [
        'high' => env('COMPATIBILITY_HIGH_THRESHOLD', 80),
        'medium' => env('COMPATIBILITY_MEDIUM_THRESHOLD', 50),
        'low' => env('COMPATIBILITY_LOW_THRESHOLD', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Questionnaire
    |--------------------------------------------------------------------------
    */
    'default_questionnaire_slug' => env('DEFAULT_QUESTIONNAIRE_SLUG', 'date-night'),

    /*
    |--------------------------------------------------------------------------
    | Love Note Configuration
    |--------------------------------------------------------------------------
    */
    'love_note_max_length' => env('LOVE_NOTE_MAX_LENGTH', 500),

    /*
    |--------------------------------------------------------------------------
    | Reminder Timings (in hours)
    |--------------------------------------------------------------------------
    */
    'reminder_hours' => env('REMINDER_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (in seconds)
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'dashboard_ttl' => env('CACHE_DASHBOARD_TTL', 300),
        'compatibility_ttl' => env('CACHE_COMPATIBILITY_TTL', 3600),
        'challenge_ttl' => env('CACHE_CHALLENGE_TTL', 86400),
        'questionnaire_ttl' => env('CACHE_QUESTIONNAIRE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Names
    |--------------------------------------------------------------------------
    */
    'queues' => [
        'emails' => env('QUEUE_EMAILS', 'emails'),
        'notifications' => env('QUEUE_NOTIFICATIONS', 'notifications'),
        'exports' => env('QUEUE_EXPORTS', 'exports'),
        'analytics' => env('QUEUE_ANALYTICS', 'analytics'),
        'default' => env('QUEUE_DEFAULT', 'default'),
    ],

];
