<?php

namespace Fabricate\Displays;

use ReflectionClass;
use ReflectionException;
use Fabricate\Contracts\Displays\DisplayException;
use Fabricate\Contracts\Displays\Interfaces\SoftwarePanel;
use Fabricate\Contracts\Chassis\CircularDependencyException;
use Fabricate\Contracts\Displays\DisplayRegistry as RegistryContract;
use Fabricate\Contracts\Displays\EmbeddedDisplay as EmbeddedDisplayInterface;

class DisplayRegistry implements RegistryContract
{
    protected array $embedded_displays = [];

    protected array $windowed_displays = [];

    public function embedded(string $type, string $circuit): EmbeddedDisplayInterface
    {
        if(isset($this->embedded_displays[$type])) {
            $panel_class = $this->embedded_displays[$type];
            return  $panel_class::circuit($circuit);
        }

        throw new DisplayException("Embedded panel type [$type] is not registered for circuit [$circuit].");
    }

    /**
     * @throws CircularDependencyException
     */
    public function window(string $driver): WindowedDisplay
    {
        if(isset($this->windowed_displays[$driver])) {
            $panel_class = $this->windowed_displays[$driver];
            $config = config("displays.windowed.{$driver}", []);
            if(empty($config)) {
                throw new DisplayException("Display [$driver] config not registered.");
            }

            $panel = new $panel_class(...$config);

            return new WindowedDisplay($panel);
        }

        throw new DisplayException("Windowed Driver [$driver] not registered.");
    }

    public function addEPanel(string $name, string $class_name): void
    {
        if($this->validateEmbeddedImplementation($class_name))
        {
            $this->embedded_displays[$name] = $class_name;
        }
    }

    public function addWPanel(string $name, string $class_name): void
    {
        if($this->validateWindowedImplementation($class_name))
        {
            $this->windowed_displays[$name] = $class_name;
        }
    }

    public function listDisplays(): array
    {
        return [
            'embedded' => $this->embedded_displays,
            'windowed' => $this->windowed_displays,
        ];
    }

    protected function validateWindowedImplementation(string $class_name): bool
    {
        try {
            $reflection = new ReflectionClass($class_name);
        } catch (ReflectionException) {
            return false;
        }

        return $reflection->isSubclassOf(SoftwarePanel::class);
    }

    protected function validateEmbeddedImplementation(string $class_name): bool
    {
        try {
            $reflection = new ReflectionClass($class_name);
        } catch (ReflectionException) {
            return false;
        }

        return $reflection->isSubclassOf(EmbeddedDisplayInterface::class);
    }
}