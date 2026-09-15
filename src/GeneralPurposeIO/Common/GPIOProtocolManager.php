<?php

namespace GeneralPurposeIO\Common;

use Voyager\Contracts\Vessel\CircularDependencyException;
use RuntimeException;
use Closure;
use Voyager\Contracts\Vessel\Vessel;
use GeneralPurposeIO\Contracts\Common\GPIOException;
use GeneralPurposeIO\Contracts\Core\GPIOProtocolFactory as FactoryContract;
use GeneralPurposeIO\Contracts\Common\GPIOCommunicationAdapterManager as AdapterManager;

class GPIOProtocolManager implements FactoryContract
{
    protected array $protocols = [];

    public function __construct(protected Vessel $program) {}

    /**
     * @param string $name
     * @return mixed
     * @throws GPIOException
     * @throws CircularDependencyException
     */
    public function protocol(string $name): AdapterManager
    {
        if (! isset($this->protocols[$name])) {
            throw GPIOException::unknownProtocol($name);
        }

        $enabled = config("gpio.protocols.{$name}.enabled", false);
        if(!$enabled)
        {
            throw new GPIOException("Protocol [{$name}] not enabled.");
        }

        return $this->protocols[$name]();
    }

    public function extend(string $name, callable $callback): void
    {
        $bound = Closure::fromCallable($callback)->bindTo($this, static::class);

        $this->protocols[$name] = $bound ?? throw new RuntimeException('Unable to bind custom protocol callback');
    }

}