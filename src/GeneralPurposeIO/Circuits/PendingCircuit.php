<?php

namespace GeneralPurposeIO\Circuits;

use GeneralPurposeIO\Contracts\Circuits\CircuitException;
use GeneralPurposeIO\Contracts\Circuits\IntegratedCircuit;

/**
 * Fluent builder for a catalog-registered IC type.
 *
 * Usage:
 *   Circuit::ic('st7789')->protocol('spi')->driver('usb')->device('ft232h')
 *       ->chipSelect(0)->dc(1)->rst(2)->make()
 *   Circuit::ic('ssd1306')->protocol('i2c')->driver('posix')->device(1)
 *       ->slave(0x3C)->args([...])->make()
 */
class PendingCircuit
{
    protected ?string $protocol = null;

    /**
     * @var array<string, mixed>
     */
    protected array $params = [];

    protected ?string $adapter = null;

    protected string|int|null $device = null;

    public function __construct(
        protected CircuitRegistry $registry,
        protected string $ic,
    ) {}

    public function protocol(string $protocol): static
    {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * Transport adapter slug (e.g. usb, posix).
     * For SPI, applies to both spi_adapter and digital_adapter unless already set via args().
     */
    public function driver(string $adapter): static
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * Bus / product device id — explicit (e.g. ft232h, ft2232h-a, or posix bus 1).
     * For SPI, applies to both spi_device and digital_device unless already set via args().
     */
    public function device(string|int $device): static
    {
        $this->device = $device;

        return $this;
    }

    public function chipSelect(string|int $chipSelect): static
    {
        $this->params['chip_select'] = $chipSelect;

        return $this;
    }

    public function dc(int $dcPin): static
    {
        $this->params['dc_pin'] = $dcPin;

        return $this;
    }

    public function rst(int $rstPin): static
    {
        $this->params['rst_pin'] = $rstPin;

        return $this;
    }

    public function slave(int $slave): static
    {
        $this->params['slave'] = $slave;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $named
     */
    public function args(array $named): static
    {
        $this->params = array_merge($this->params, $named);

        return $this;
    }

    /**
     * CamelCase unknown setters map to snake_case factory params.
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): static
    {
        if ($arguments === []) {
            throw new CircuitException("Circuit fluent setter [{$name}] requires a value.");
        }

        $this->params[$this->toSnakeCase($name)] = $arguments[0];

        return $this;
    }

    /**
     * @throws CircuitException
     */
    public function make(): IntegratedCircuit
    {
        if (is_null($this->protocol) || $this->protocol === '') {
            throw new CircuitException('Circuit protocol is required before make().');
        }

        return $this->registry->build($this->ic, $this->protocol, $this->resolvedParams());
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolvedParams(): array
    {
        $params = $this->params;
        $isSpi = strtolower($this->protocol ?? '') === 'spi';

        if (! is_null($this->adapter)) {
            if ($isSpi) {
                $params['spi_adapter'] ??= $this->adapter;
                $params['digital_adapter'] ??= $this->adapter;
            } else {
                $params['adapter'] ??= $this->adapter;
            }
        }

        if (! is_null($this->device)) {
            if ($isSpi) {
                $params['spi_device'] ??= $this->device;
                $params['digital_device'] ??= $this->device;
            } else {
                $params['device'] ??= $this->device;
            }
        }

        return $params;
    }

    protected function toSnakeCase(string $name): string
    {
        $snake = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $name);

        return strtolower($snake ?? $name);
    }
}
