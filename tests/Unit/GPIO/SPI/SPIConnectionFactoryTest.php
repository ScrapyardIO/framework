<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\Digital\FakeDigitalCarrierDriverManager;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\FakeCarrierDriverManager;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\SPI\FakeSPICarrierDriverManager;
use DeptOfScrapyardRobotics\Tests\Fixtures\GPIO\SPI\FakeSPIDriverAdapter;
use GPIO\Common\GPIO;
use GPIO\Common\GPIOCarriers;
use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\Digital\LineBias;
use GPIO\Contracts\SPI\SPIEndianness;
use GPIO\Contracts\SPI\SPIException;
use GPIO\Contracts\SPI\SPIMode;
use GPIO\Digital\Input\DigitalInputConnectionFactory;
use GPIO\Digital\Output\DigitalOutputConnectionFactory;
use GPIO\SPI\SPI;
use GPIO\SPI\SPIConnectionFactory;
use ScrapyardIO\NutsAndBolts\Reflection;

// driver_adapter is protected with no accessor, so reflection is needed to
// reach into the factory and assert what the underlying fake driver
// actually recorded.
function spiFactoryDriverAdapter(SPIConnectionFactory $factory): FakeSPIDriverAdapter
{
    $property = new ReflectionProperty($factory, 'driver_adapter');
    $property->setAccessible(true);

    return $property->getValue($factory);
}

function resetGpioAndCarriersSingletonsForSpi(): void
{
    foreach ([GPIO::class, GPIOCarriers::class] as $class) {
        $property = (new ReflectionClass($class))->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}

beforeEach(function () {
    resetGpioAndCarriersSingletonsForSpi();

    // Also boots the 'fake-digital' carrier, purely so the "additional
    // digital pins" test below can build a DigitalOutputConnectionFactory
    // to bundle onto the SPI factory without touching a real hardware
    // carrier - SPIConnectionFactory itself only ever talks to 'fake-spi'.
    GPIOCarriers::boot([
        'fake-spi' => FakeSPICarrierDriverManager::class,
        'fake-digital' => FakeDigitalCarrierDriverManager::class,
    ]);
});

afterEach(function () {
    resetGpioAndCarriersSingletonsForSpi();
});

test('it is registered under the spi protocol', function () {
    $attribute = Reflection::reflect_class(SPIConnectionFactory::class, CarrierFactory::class);

    expect($attribute)->not->toBeNull()
        ->and($attribute->newInstance()->protocol)->toBe('spi');
});

test('its defaults match a plain, unconfigured SPI connection', function () {
    $factory = new SPIConnectionFactory('fake-spi');

    expect($factory->master_host_device)->toBeNull()
        ->and($factory->spi_mode)->toBe(SPIMode::MODE_0)
        ->and($factory->chip_select)->toBe(0)
        ->and($factory->speed)->toBe(800_000)
        ->and($factory->bits_per_word)->toBe(8)
        ->and($factory->endianness)->toBe(SPIEndianness::MSB)
        ->and($factory->digital_pins)->toBe([]);
});

test('device(), mode(), speed(), endianness(), chipSelect() and bitsPerByte() are fluent and set the expected properties', function () {
    $factory = new SPIConnectionFactory('fake-spi');

    $result = $factory
        ->device('/dev/spidev0.0')
        ->mode(SPIMode::MODE_3)
        ->speed(1_000_000)
        ->endianness(SPIEndianness::LSB)
        ->chipSelect(1)
        ->bitsPerByte(16);

    expect($result)->toBe($factory)
        ->and($factory->master_host_device)->toBe('/dev/spidev0.0')
        ->and($factory->spi_mode)->toBe(SPIMode::MODE_3)
        ->and($factory->speed)->toBe(1_000_000)
        ->and($factory->endianness)->toBe(SPIEndianness::LSB)
        ->and($factory->chip_select)->toBe(1)
        ->and($factory->bits_per_word)->toBe(16);
});

test('create() throws when no master device has been set', function () {
    (new SPIConnectionFactory('fake-spi'))->create();
})->throws(SPIException::class, 'SPI Master device is missing.');

test('create() does not require a chip select, mode, speed, bits per word or endianness to be explicitly set', function () {
    $result = (new SPIConnectionFactory('fake-spi'))
        ->device('/dev/spidev0.0')
        ->create();

    expect($result)->toBeInstanceOf(SPI::class);
});

test('create() forwards every configured option to the driver adapter', function () {
    $factory = (new SPIConnectionFactory('fake-spi'))
        ->device('/dev/spidev0.0')
        ->mode(SPIMode::MODE_2)
        ->speed(500_000)
        ->endianness(SPIEndianness::LSB)
        ->chipSelect(1)
        ->bitsPerByte(16)
        ->digitalPins(0);

    $driver = spiFactoryDriverAdapter($factory);

    $result = $factory->create();

    expect($result)->toBeInstanceOf(SPI::class)
        ->and($driver->buildConnectionCalls)->toBe([
            [
                'master' => '/dev/spidev0.0',
                'chip_select' => 1,
                'spi_mode' => SPIMode::MODE_2,
                'speed' => 500_000,
                'bits_per_word' => 16,
                'endianness' => SPIEndianness::LSB,
                'gpio_chip' => 0,
                'digital_pins' => [],
            ],
        ]);
});

test('create() forwards any additional digital pins bundled onto the factory', function () {
    $factory = (new SPIConnectionFactory('fake-spi'))->device('/dev/spidev0.0');
    $resetPin = new DigitalOutputConnectionFactory('fake-digital');
    $factory->digital_pins[] = $resetPin;

    $driver = spiFactoryDriverAdapter($factory);

    $factory->create();

    expect($driver->buildConnectionCalls[0]['digital_pins'])->toBe([$resetPin]);
});

// digitalIn()/digitalOut() come from the DigitalPinsOnBus trait and go
// through the real GPIO facade (GPIO::digitalIn()/digitalOut()), so these
// need a fake carrier that itself supports digital-in/digital-out
// alongside spi (unlike FakeSPICarrierDriverManager, which only supports
// spi).

test('digitalIn() appends a configured DigitalInputConnectionFactory to digital_pins and is fluent', function () {
    resetGpioAndCarriersSingletonsForSpi();
    GPIOCarriers::boot(['fake' => FakeCarrierDriverManager::class]);
    $factory = new SPIConnectionFactory('fake');

    $result = $factory->digitalIn(17, 'clock', [true, false], 500, LineBias::PULL_UP);

    expect($result)->toBe($factory)
        ->and($factory->digital_pins)->toHaveCount(1)
        ->and($factory->digital_pins[0])->toBeInstanceOf(DigitalInputConnectionFactory::class);
});

test('digitalOut() appends a configured DigitalOutputConnectionFactory to digital_pins and is fluent', function () {
    resetGpioAndCarriersSingletonsForSpi();
    GPIOCarriers::boot(['fake' => FakeCarrierDriverManager::class]);
    $factory = new SPIConnectionFactory('fake');

    $result = $factory->digitalOut(27, 'reset', true);

    expect($result)->toBe($factory)
        ->and($factory->digital_pins)->toHaveCount(1)
        ->and($factory->digital_pins[0])->toBeInstanceOf(DigitalOutputConnectionFactory::class);
});
