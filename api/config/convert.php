<?php

return [
    /*
     * Where the conversion service lives, and the secret it shares with this
     * site. Both are set on the server only: the browser never sees either,
     * because the site proxies for it.
     *
     * With them unset the conversion endpoints answer 503 and say so, rather
     * than failing in a way that looks like a bug.
     */
    'url' => env('CONVERT_SERVICE_URL'),
    'key' => env('CONVERT_SERVICE_KEY'),

    // Megabytes, checked here before the upload is forwarded. The service has
    // its own limits; these are the first line, not the last.
    'max_document_mb' => (int) env('CONVERT_MAX_DOCUMENT_MB', 30),
    'max_audio_mb' => (int) env('CONVERT_MAX_AUDIO_MB', 80),
];
