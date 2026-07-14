<?php

namespace GPIO\Common;

use GPIO\Contracts\Common\CarrierDriver;
use GPIO\Contracts\Common\CarrierDriverManager as CarrierDriverManagerInterface;
use ReflectionClass;
use ReflectionException;
use ScrapyardIO\NutsAndBolts\Action;
use ScrapyardIO\NutsAndBolts\Reflection;

class LoadDefaultProtocolManagers extends Action
{
    /**
     * @param  string|null  $directory  microscrap packages root; defaults to monorepo sibling layout
     * @return array<string, class-string<CarrierDriverManagerInterface>>
     *
     * @throws ReflectionException
     */
    public static function run(?string $directory = null): array
    {
        $results = [];

        $dirname = $directory ?? dirname(__DIR__).'/../../../../microscrap';
        $classes = Reflection::classes_in_packages_directory($dirname);

        foreach ($classes as $class) {
            if (! is_subclass_of($class, CarrierDriverManagerInterface::class)) {
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
