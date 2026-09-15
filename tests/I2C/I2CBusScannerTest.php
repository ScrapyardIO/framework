<?php

use GeneralPurposeIO\I2C\I2CBusScanner;
use ScrapyardIO\Tests\Support\Fakes\FakeI2CDriver;

it('renders an i2cdetect-style grid', function () {
    $output = (new I2CBusScanner(new FakeI2CDriver()))->render();

    expect($output)->toContain('     0  1  2  3  4  5  6  7  8  9  a  b  c  d  e  f')
        ->toContain('30:')->toContain('3c ')->toContain('38 ')->toContain('-- ')
        ->toMatch('/^00: {9}/m');
});
