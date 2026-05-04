<?php

return [
    'disk' => env('FILESYSTEM_DISK') === 's3' ? 's3' : 'userimages',
    'sizes' => [
        'sm' => ['width' => 300, 'height' => 300],
        'md' => ['width' => 600, 'height' => 600],
        'lg' => ['width' => 900, 'height' => 900],
    ],
    'original_path' => 'profile/originals',
    'reformat_path' => 'profile/variants/%sx%s',
    'format' => ['webp', 'jpg', 'png', 'jpeg'],
    'compression' => 80,
];
