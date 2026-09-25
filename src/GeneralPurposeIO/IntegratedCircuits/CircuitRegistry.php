<?php

namespace GeneralPurposeIO\IntegratedCircuits;

use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitException;
use GeneralPurposeIO\Contracts\IntegratedCircuits\CircuitRegistry as RegistryContract;
use GeneralPurposeIO\Contracts\IntegratedCircuits\IntegratedCircuit;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * The chip catalog. A chip package's provider calls addCircuit() with what it
 * ships; nothing is instantiated until someone asks.
 *
 * conjure('st7789') reads the wiring the app keeps in
 * config/circuits/st7789.php — default_config, or the config named — and
 * builds the chip from it. The build is always the chip's own public static
 * factory, named after the config's protocol and invoked with the config's
 * keys as named arguments, so the registry knows no chip's constructor.
 */
class CircuitRegistry implements RegistryContract
{
    /** @var array<string, class-string<IntegratedCircuit>> */
    protected array $circuits = [];

    public function addCircuit(string $slug, string $class_name): void
    {
        if ($this->isCircuit($class_name)) {
            $this->circuits[$slug] = $class_name;
        }
    }

    public function has(string $slug): bool
    {
        return isset($this->circuits[$slug]);
    }

    /** @return array<string, class-string<IntegratedCircuit>> */
    public function listCircuits(): array
    {
        return $this->circuits;
    }

    /** @return class-string<IntegratedCircuit> */
    public function resolveClass(string $slug): string
    {
        return $this->circuits[$slug] ?? throw CircuitException::notRegistered($slug);
    }

    public function conjure(string $slug, ?string $config = null): IntegratedCircuit
    {
        $this->resolveClass($slug);
        $tree = config("circuits.{$slug}");

        if (! is_array($tree)) {
            throw CircuitException::noCircuitConfig($slug);
        }

        $name = $config ?? ($tree['default_config'] ?? null);

        if (! is_string($name) || $name === '') {
            throw CircuitException::noDefaultConfig($slug);
        }

        $wiring = $tree['configs'][$name] ?? null;

        if (! is_array($wiring)) {
            throw CircuitException::noSuchConfig($slug, $name);
        }

        // A config is named after its protocol unless it says otherwise, so an
        // app may keep 'left' and 'right' beside 'spi'.
        $protocol = $wiring['protocol'] ?? $name;
        unset($wiring['protocol']);

        return $this->build($slug, (string) $protocol, $wiring);
    }

    /** @param array<string, mixed> $params */
    public function build(string $slug, string $protocol, array $params): IntegratedCircuit
    {
        $class = $this->resolveClass($slug);
        $factory = $this->factory($slug, $class, $protocol);
        $named = [];

        foreach ($factory->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $params)) {
                $named[$name] = $params[$name];

                continue;
            }

            if (! $parameter->isDefaultValueAvailable()) {
                throw CircuitException::missingParameter($slug, $protocol, $name);
            }
        }

        $circuit = $factory->invokeArgs(null, $named);

        return $circuit instanceof IntegratedCircuit
            ? $circuit
            : throw CircuitException::factoryReturnedNonCircuit($slug, $protocol);
    }

    protected function factory(string $slug, string $class, string $protocol): ReflectionMethod
    {
        try {
            $method = new ReflectionMethod($class, $protocol);
        } catch (ReflectionException $e) {
            throw CircuitException::noSuchFactory($slug, $class, $protocol, $e);
        }

        return $method->isStatic() && $method->isPublic()
            ? $method
            : throw CircuitException::factoryNotPublicStatic($slug, $protocol);
    }

    protected function isCircuit(string $class_name): bool
    {
        try {
            return (new ReflectionClass($class_name))->implementsInterface(IntegratedCircuit::class);
        } catch (ReflectionException) {
            return false;
        }
    }
}
