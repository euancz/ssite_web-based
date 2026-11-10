<?php

return [
    // Exact origins allowed to receive the browser app's one-time sign-in code.
    'web_origins' => array_values(array_filter(array_map('trim', explode(',', env('SSITE_WEB_ORIGINS', ''))))),
];
