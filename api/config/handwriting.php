<?php

return [
    /*
     * The browser calls this Laravel proxy, never the Hugging Face Space
     * directly. Keep the shared key in server environment variables only.
     */
    'url' => env('HANDWRITING_SERVICE_URL'),
    'key' => env('HANDWRITING_SERVICE_KEY'),
    'space_token' => env('HANDWRITING_SPACE_TOKEN'),
    'max_image_mb' => (int) env('HANDWRITING_MAX_IMAGE_MB', 8),
];
