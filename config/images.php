<?php

return [
    'disk' => env('FILESYSTEM_DISK') === 's3' ? 's3' : 'userimages',
    'sizes' => [
        'xs' => ['width' => 64,  'height' => 64],
        'sm' => ['width' => 128, 'height' => 128],
        'md' => ['width' => 256, 'height' => 256],
        'lg' => ['width' => 512, 'height' => 512],
    ],
    'original_path' => 'profile/originals',
    'reformat_path' => 'profile/variants/%sx%s',
    'format' => ['webp', 'jpg', 'png', 'jpeg'],
    'compression' => 80,
];