<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rich assistant content (images)
    |--------------------------------------------------------------------------
    |
    | Default source is Wikimedia (no API key). Optional: unsplash, pexels
    | with keys in config/services.php.
    |
    */

    'image_source' => env('IMAGE_SOURCE', 'wikimedia'),

];
