<?php

return [
    // Public release/store URL. Leave empty until the mobile app is published.
    'download_url' => env('SSITE_APP_DOWNLOAD_URL'),

    // Exact origins allowed to receive the browser app's one-time sign-in code.
    'web_origins' => array_values(array_filter(array_map('trim', explode(',', env('SSITE_WEB_ORIGINS', ''))))),
];
