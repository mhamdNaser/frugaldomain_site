<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Icon artwork location
    |--------------------------------------------------------------------------
    |
    | The SVG/PNG files are owned by the API application and live in its public
    | directory. On the server the two applications sit side by side:
    |
    |   public_html/api/public/icons   <- artwork
    |   public_html/cdn/public         <- this application
    |
    | so the default resolves to "../api/public". Paths stored in the
    | icon_files table ("icons/star.svg") are appended to this root.
    |
    */

    'public_path' => env('ICONS_PUBLIC_PATH', base_path('../api/public')),

];
