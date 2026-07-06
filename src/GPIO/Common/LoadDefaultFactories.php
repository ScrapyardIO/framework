<?php

namespace GPIO\Common;

use ReflectionClass;
use ReflectionException;
use ScrapyardIO\NutsAndBolts\Action;
use ScrapyardIO\NutsAndBolts\Reflection;
use GPIO\Contracts\Common\CarrierFactory;
use GPIO\Contracts\Common\GPIOConnectionFactory as GPIOConnectionFactoryInterface;

class LoadDefaultFactories extends Action
{
    /**
     * @throws ReflectionException
     */
    public static function run(): array
    {
        $results = [];

        $classes = Reflection::classes_in_namespace('GPIO', dirname(__DIR__));

        foreach ($classes as $class) {
            if (!is_subclass_of($class, GPIOConnectionFactoryInterface::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            $attribute = Reflection::reflect_class($class, CarrierFactory::class);

            if (is_null($attribute)) {
                continue;
            }

            /** @var CarrierFactory $carrier_factory */
            $carrier_factory = $attribute->newInstance();
            $results[$carrier_factory->protocol] = $class;
        }

        return $results;
    }
}
