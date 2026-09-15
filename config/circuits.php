<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Profile Definitions
    |--------------------------------------------------------------------------
    |
    | Named recipes for Circuit::profile('name'). Keys are arbitrary.
    |
    | Each profile must include:
    |   ic       — catalog slug from Circuit::addCircuit()
    |   protocol — static factory on the IC class (i2c, spi, uart, …)
    |   params   — named factory arguments (always include boot_now when useful)
    |
    | Scaffold interactively (asks driver/device/pins from #[Pinout]):
    |   php workshop circuit:make-profile
    |   php workshop st77xx:make-profile
    |
    | Publish this file into the app:
    |   php workshop vendor:publish --tag=gpio-circuits-config
    |
    | On-demand build (device is always explicit):
    |   Circuit::ic('st7789')->protocol('spi')->driver('usb')->device('ft232h')
    |       ->chipSelect(0)->dc(1)->rst(2)->make()
    |
    |--------------------------------------------------------------------------
    */

    // 'front_panel' => [
    //     'ic' => 'st7789',
    //     'protocol' => 'spi',
    //     'params' => [
    //         'boot_now' => true,
    //         'spi_adapter' => 'usb',
    //         'spi_device' => 'ft232h',
    //         'chip_select' => 0,
    //         'digital_adapter' => 'usb',
    //         'digital_device' => 'ft232h',
    //         'dc_pin' => 1,
    //         'rst_pin' => 2,
    //     ],
    // ],

];
