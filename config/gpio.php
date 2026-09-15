<?php

return [
    'protocols' => [
        'uart' => [
            'enabled' => true,
            'default' => 'posix',
            'adapters' => [
                'posix' => \GeneralPurposeIO\UART\Adapters\PosixUARTAdapter::class,
                'usb' => \GeneralPurposeIO\UART\Adapters\FtdiUARTAdapter::class,
            ]
        ],
        'i2c' => [
            'enabled' => true,
            'default' => 'posix',
            'adapters' => [
                'posix' => \GeneralPurposeIO\I2C\Adapters\PosixI2CAdapter::class,
                'usb' => \GeneralPurposeIO\I2C\Adapters\MpsseI2CAdapter::class,
            ],
        ],
        'spi' => [
            'enabled' => true,
            'default' => 'posix',
            'adapters' => [
                'posix' => \GeneralPurposeIO\SPI\Adapters\PosixSPIAdapter::class,
                'usb' => \GeneralPurposeIO\SPI\Adapters\MpsseSPIAdapter::class,
            ],
        ],
        'digital-io' => [
            'enabled' => true,
            'default' => 'posix',
            'adapters' => [
                'posix' => \GeneralPurposeIO\Digital\Adapters\PosixDigitalIOAdapter::class,
                'usb' => \GeneralPurposeIO\Digital\Adapters\MpsseDigitalIOAdapter::class,
            ]
        ],
        'pwm' => [
            'enabled' => false,
            'default' => 'native',
            'adapters' => [
                'native' => \GeneralPurposeIO\PWM\Adapters\NativePWMAdapter::class,
            ],
        ],
        'analog-in' => [
            'enabled' => false,
            'default' => 'arduino',
            'adapters' => [
                'arduino' => \GeneralPurposeIO\Analog\Adapters\ADC\ArduinoADCAdapter::class,
            ],
        ],
        'analog-out' => [
            'enabled' => false,
            'default' => null,
            'adapters' => [],
        ],
    ],
    'io_pools' => [
        'enabled' => true,
        'defer_per_tick' => null,
    ],
];