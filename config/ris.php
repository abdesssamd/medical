<?php

return [
    // Active ou desactive tout le module RIS.
    'enabled' => env('RIS_ENABLED', true),

    // Configuration Orthanc dediee au module RIS.
    'orthanc' => [
        'base_url' => env('RIS_ORTHANC_BASE_URL', env('ORTHANC_BASE_URL')),
        'username' => env('RIS_ORTHANC_USERNAME', env('ORTHANC_USERNAME')),
        'password' => env('RIS_ORTHANC_PASSWORD', env('ORTHANC_PASSWORD')),
        'timeout' => (int) env('RIS_ORTHANC_TIMEOUT', env('ORTHANC_TIMEOUT', 8)),
        'worklist_path' => env('RIS_ORTHANC_WORKLIST_PATH', env('ORTHANC_WORKLIST_PATH', '/worklists')),
        'webhook_token' => env('RIS_ORTHANC_WEBHOOK_TOKEN', env('ORTHANC_WEBHOOK_TOKEN')),
        'viewer_base_url' => env('RIS_ORTHANC_VIEWER_BASE_URL', env('RIS_ORTHANC_BASE_URL', env('ORTHANC_BASE_URL'))),
    ],

    'reports' => [
        'share_valid_days' => (int) env('RIS_REPORT_SHARE_VALID_DAYS', 30),
    ],

    'ai' => [
        'enabled' => env('RIS_AI_ENABLED', false),
        'base_url' => env('RIS_AI_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('RIS_AI_API_KEY'),
        'model' => env('RIS_AI_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('RIS_AI_TIMEOUT', 12),
        'chat_path' => env('RIS_AI_CHAT_PATH', '/chat/completions'),
    ],
];
