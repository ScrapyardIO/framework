<?php

namespace GPIO\Common;

use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterInterface;
use GPIO\Contracts\Common\GPIOException;
use ReflectionException;
use ScrapyardIO\NutsAndBolts\ScrapyardLibrary;
use GPIO\Contracts\Common\CarrierDriverManager as CarrierDriverManagerInterface;

class GPIOCarriers extends ScrapyardLibrary
{
    protected static ?self $instance = null;

    protected array $carrier_libraries = [];

    /**
     * @throws GPIOException|ReflectionException
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $carrier = self::boot();

        if(isset($carrier->libraryManagers()[$name])) {
            $driver = $arguments[0];
            $manager_class = $carrier->libraryManagers()[$name];

            /** @var CarrierDriverManagerInterface $manager */
            $manager = new $manager_class;

            /** @var GPIODriverAdapterInterface */
            return (new $manager)->driver($driver);
        }

        throw GPIOException::invalidStaticMethod($name, static::class);
    }

    protected function libraryManagers(): array
    {
        return $this->carrier_libraries;
    }

    protected function manager(string $library, string $manager): void
    {
        $this->carrier_libraries[$library] = $manager;
    }

    protected static function getInstance(): self
    {
        return self::$instance;
    }

    /**
     * @throws ReflectionException
     */
    public static function boot(?array $protocols = null): static
    {
        $libraries = $protocols;

        if(self::$instance === null) {
            $self = new self();
            $libraries ??= LoadDefaultProtocolManagers::run();

            foreach($libraries as $library => $manager) {
                $self->manager($library, $manager);
            }
            self::$instance = $self;
        }

        return self::$instance;
    }
}
