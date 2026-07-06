<?php

namespace GPIO\Common;

use ReflectionException;
use ScrapyardIO\NutsAndBolts\Reflection;
use GPIO\Contracts\Common\GPIOException;
use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\Common\GPIOConnectionFactory as GPIOConnectionFactoryContract;
use GPIO\Contracts\Common\GPIODriverAdapter as GPIODriverAdapterInterface;

abstract class GPIOConnectionFactory implements GPIOConnectionFactoryContract
{
    protected GPIODriverAdapterInterface $driver_adapter;

    /**
     * @throws GPIOException|ReflectionException
     */
    public function __construct(
        public readonly string $driver
    ) {
        $this->driver_adapter = GPIOCarriers::{$driver}(
            $this->getCarrierProtocol()
        );
    }

    /**
     * @throws GPIOException|ReflectionException
     */
    protected function getCarrierProtocol(): string
    {
        $attribute = Reflection::reflect_class(static::class, CarrierFactory::class);
        if (!is_null($attribute)) {
            /** @var CarrierFactory $carrier_factory */
            $carrier_factory = $attribute->newInstance();
            return $carrier_factory->protocol;
        }

        throw GPIOException::carrierFactoryNotImplemented(static::class);
    }
}
