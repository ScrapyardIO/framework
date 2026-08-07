<?php

namespace Fabricate\Database;

use Fabricate\Chassis\Contracts\WireframeServiceContainer;
use Fabricate\Console\Command;
use Fabricate\Console\View\Components\TwoColumnDetail;
use Fabricate\Contracts\Chassis\ServiceContainer;
use Fabricate\Database\Console\Seeds\WithoutModelEvents;
use Fabricate\NutsAndBolts\Arr;
use InvalidArgumentException;

abstract class Seeder
{
    /**
     * The container instance.
     *
     * @var \Fabricate\Chassis\Contracts\WireframeServiceContainer
     */
    protected $container;

    /**
     * The console command instance.
     *
     * @var \Fabricate\Console\Command
     */
    protected $command;

    /**
     * Seeders that have been called at least one time.
     *
     * @var array
     */
    protected static $called = [];

    /**
     * Run the given seeder class.
     *
     * @param  array|string  $class
     * @param  bool  $silent
     * @param  array  $parameters
     * @return $this
     */
    public function call($class, $silent = false, array $parameters = [])
    {
        $classes = Arr::wrap($class);

        foreach ($classes as $class) {
            $seeder = $this->resolve($class);

            $name = get_class($seeder);

            if ($silent === false && isset($this->command)) {
                (new TwoColumnDetail($this->command->getOutput()))
                    ->render($name, '<fg=yellow;options=bold>RUNNING</>');
            }

            $startTime = microtime(true);

            $seeder->__invoke($parameters);

            if ($silent === false && isset($this->command)) {
                $runTime = number_format((microtime(true) - $startTime) * 1000);

                (new TwoColumnDetail($this->command->getOutput()))
                    ->render($name, "<fg=gray>$runTime ms</> <fg=green;options=bold>DONE</>");

                $this->command->getOutput()->writeln('');
            }

            static::$called[] = $class;
        }

        return $this;
    }

    /**
     * Run the given seeder class.
     *
     * @param  array|string  $class
     * @param  array  $parameters
     * @return void
     */
    public function callWith($class, array $parameters = [])
    {
        $this->call($class, false, $parameters);
    }

    /**
     * Silently run the given seeder class.
     *
     * @param  array|string  $class
     * @param  array  $parameters
     * @return void
     */
    public function callSilent($class, array $parameters = [])
    {
        $this->call($class, true, $parameters);
    }

    /**
     * Run the given seeder class once.
     *
     * @param  array|string  $class
     * @param  bool  $silent
     * @return void
     */
    public function callOnce($class, $silent = false, array $parameters = [])
    {
        $classes = Arr::wrap($class);

        foreach ($classes as $class) {
            if (in_array($class, static::$called)) {
                continue;
            }

            $this->call($class, $silent, $parameters);
        }
    }

    /**
     * Resolve an instance of the given seeder class.
     *
     * @param  string  $class
     * @return \Fabricate\Database\Seeder
     */
    protected function resolve($class)
    {
        if (isset($this->container)) {
            $instance = $this->container->make($class);

            $instance->setContainer($this->container);
        } else {
            $instance = new $class;
        }

        if (isset($this->command)) {
            $instance->setCommand($this->command);
        }

        return $instance;
    }

    /**
     * Set the IoC container instance.
     *
     * @return $this
     */
    public function setContainer(ServiceContainer|WireframeServiceContainer $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set the console command instance.
     *
     * @param  \Fabricate\Console\Command  $command
     * @return $this
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Run the database seeds.
     *
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function __invoke(array $parameters = [])
    {
        if (! method_exists($this, 'run')) {
            throw new InvalidArgumentException('Method [run] missing from '.get_class($this));
        }

        $callback = fn () => isset($this->container)
            ? $this->container->call([$this, 'run'], $parameters)
            : $this->run(...$parameters);

        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[WithoutModelEvents::class])) {
            $callback = $this->withoutModelEvents($callback);
        }

        return $callback();
    }
}
