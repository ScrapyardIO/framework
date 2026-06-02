<?php

namespace Waveforms;

use Exception;
use Waveforms\Factory\CarrierFactory;

abstract class CarrierService
{
    protected array $drivers;

    /** @var array<class-string<static>, static> */
    private static array $instances = [];

    private function __construct() {}

    public function isValidDriver(string $driver): bool
    {
        return isset($this->drivers[$driver]);
    }

    /**
     * @throws Exception
     */
    public function get(string $driver): CarrierFactory
    {
        if ($this->isValidDriver($driver)) {
            return new $this->drivers[$driver]['factory'];
        }

        throw new Exception("Invalid driver: $driver");
    }

    public static function getInstance(): static
    {
        return self::$instances[static::class] ??= new static;
    }
}
