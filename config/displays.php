<?php

return [
    'embedded' => [],
    'windowed' => [
        'default' => 'sdl3',
        'drivers' => [
            'glfw' => [
                'width' => 800,
                'height' => 600,
                'title' => 'ScrapyardIO GLFW',
                'boot_now' => true,
            ],
            'sdl3' => [
                'width' => 800,
                'height' => 600,
                '_scale_factor' => 1,
                'title' => 'ScrapyardIO SDL3',
                'boot_now' => true,
            ],
        ],
    ],
];
