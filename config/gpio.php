<?php

return [
    'protocols' => [
        'i2c' => [
            'default' => 'posix',
            'adapters' => [
                'posix' => [],
                'usb' => []
            ]
        ],
        'spi' => [
            'default' => 'posix',
            'adapters' => [
                'posix' => [],
                'usb' => []
            ]
        ],
        'uart' => [
            'default' => 'posix',
            'adapters' => [
                'posix' => [],
                'usb' => []
            ]
        ],
        'pwm' => [
            'default' => 'native',
            'adapters' => [
                'native' => [],
            ]
        ],
        'digital-in' => [
            'default' => 'posix',
            'adapters' => [
                'posix' => [],
                'usb' => []
            ]
        ],
        'digital-out' => [
            'default' => 'posix',
            'adapters' => [
                'posix' => [],
                'usb' => []
            ]
        ],
    ]
];
