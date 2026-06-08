<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'medication_api' => [
        'enabled' => env('MEDICATION_API_ENABLED', false),
        'provider' => env('MEDICATION_API_PROVIDER', 'vidal'),
        'base_url' => env('MEDICATION_API_BASE_URL'),
        'api_key' => env('MEDICATION_API_KEY'),
        'auth_mode' => env('MEDICATION_API_AUTH_MODE', 'x_api_key'), // x_api_key|bearer
        'auth_header' => env('MEDICATION_API_AUTH_HEADER', 'X-API-KEY'),
        'timeout' => env('MEDICATION_API_TIMEOUT', 8),
        'search_path' => env('MEDICATION_API_SEARCH_PATH', '/medications/search'),
        'safety_path' => env('MEDICATION_API_SAFETY_PATH', '/medications/safety-check'),
        'ping_path' => env('MEDICATION_API_PING_PATH', '/status'),
    ],

    'orthanc' => [
        'base_url' => env('ORTHANC_BASE_URL'),
        'username' => env('ORTHANC_USERNAME'),
        'password' => env('ORTHANC_PASSWORD'),
        'timeout' => env('ORTHANC_TIMEOUT', 8),
        'worklist_path' => env('ORTHANC_WORKLIST_PATH', '/worklists'),
        'worklist_directory' => env('ORTHANC_WORKLIST_DIRECTORY', 'storage:app/orthanc/worklists'),
        'dump2dcm_path' => env('ORTHANC_DUMP2DCM_PATH'),
        'webhook_token' => env('ORTHANC_WEBHOOK_TOKEN'),
        'dicom_uid_root' => env('DICOM_UID_ROOT', '1.2.826.0.1.3680043.10.5432'),
    ],

];
