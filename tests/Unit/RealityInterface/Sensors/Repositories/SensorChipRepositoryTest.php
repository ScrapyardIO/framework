<?php

use BareMetal\Repositories\IntegratedCircuitRepository;
use RealityInterface\Sensors\Exceptions\SensorRepoException;
use RealityInterface\Sensors\Repositories\SensorChipRepository;
use RealityInterface\Sensors\SensorChip;

class FakeRegisteredChip extends SensorChip
{
    public static function connection(...$args): static
    {
        return new static;
    }

    public function create(): static
    {
        return $this;
    }
}

class FakeFailingChip extends SensorChip
{
    public static function connection(...$args): static
    {
        return new static;
    }

    public function create(): false
    {
        return false;
    }
}

function resetIntegratedCircuitRepositoryInstance(): void
{
    $reflection = new ReflectionClass(IntegratedCircuitRepository::class);
    $property = $reflection->getProperty('instance');
    $property->setValue(null, null);
}

function writeSensorRepositoryConfig(array $sensors): string
{
    $dir = sys_get_temp_dir().'/scrapyard-sensors-'.uniqid('', true);
    mkdir($dir, 0777, true);

    $entries = [];
    foreach ($sensors as $name => $chipClass) {
        $entries[$name] = [
            'class_name' => $chipClass,
            'connection' => [],
            'startup' => [],
        ];
    }

    $export = var_export(['sensors' => $entries], true);
    file_put_contents("{$dir}/scrapyard-io.php", "<?php\n\nreturn {$export};\n");

    return $dir;
}

beforeEach(function () {
    $this->configDir = writeSensorRepositoryConfig([
        'test-chip' => FakeRegisteredChip::class,
    ]);

    putenv('SCRAPYARD_CONFIG_PATH='.$this->configDir);
    resetIntegratedCircuitRepositoryInstance();
});

afterEach(function () {
    if (isset($this->configDir) && is_dir($this->configDir)) {
        unlink($this->configDir.'/scrapyard-io.php');
        rmdir($this->configDir);
    }

    putenv('SCRAPYARD_CONFIG_PATH');
    resetIntegratedCircuitRepositoryInstance();
});

it('reports a registered sensor key via hasSensor', function () {
    expect(SensorChipRepository::getInstance()->hasSensor('test-chip'))->toBeTrue();
});

it('reports an unknown sensor key via hasSensor', function () {
    expect(SensorChipRepository::getInstance()->hasSensor('missing-chip'))->toBeFalse();
});

it('returns the SensorChip instance for a registered key', function () {
    $sensor = SensorChipRepository::sensor('test-chip');

    expect($sensor)->toBeInstanceOf(SensorChip::class)
        ->and($sensor)->toBeInstanceOf(FakeRegisteredChip::class);
});

it('throws SensorRepoException when the sensor key is not registered', function () {
    SensorChipRepository::sensor('missing-chip');
})->throws(SensorRepoException::class, "Sensor 'missing-chip' is not registered");

it('throws SensorRepoException when getSensor returns false', function () {
    unlink($this->configDir.'/scrapyard-io.php');
    rmdir($this->configDir);

    $this->configDir = writeSensorRepositoryConfig([
        'failing-chip' => FakeFailingChip::class,
    ]);
    putenv('SCRAPYARD_CONFIG_PATH='.$this->configDir);
    resetIntegratedCircuitRepositoryInstance();

    SensorChipRepository::sensor('failing-chip');
})->throws(SensorRepoException::class, "Sensor 'failing-chip' is not registered");
