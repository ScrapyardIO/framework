<?php

namespace GPIO\Common;

use GPIO\Contracts\Common\CarrierDriver;
use ReflectionClass;
use ReflectionException;
use ScrapyardIO\NutsAndBolts\Action;
use ScrapyardIO\NutsAndBolts\Reflection;
use GPIO\Contracts\Common\CarrierDriverManager as CarrierDriverManagerInterface;

class LoadDefaultProtocolManagers extends Action
{
    /**
     * @throws ReflectionException
     */
    public static function run(): array
    {
        $results = [];

        $dirname = dirname(__DIR__)."/../../../../microscrap";
        $classes = Reflection::classes_in_packages_directory($dirname);

        foreach ($classes as $class) {
            if (!is_subclass_of($class, CarrierDriverManagerInterface::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            $attribute = Reflection::reflect_class($class, CarrierDriver::class);

            if (is_null($attribute)) {
                continue;
            }

            /** @var CarrierDriver $carrier_factory */
            $carrier_factory = $attribute->newInstance();
            $results[$carrier_factory->driver] = $class;
        }

        return $results;
    }
}
