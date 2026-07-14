<?php

namespace BareMetal\Console;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class ContainerCommandLoader implements CommandLoaderInterface
{
    /**
     * @param  array<string, Command|string>  $command_map
     */
    public function __construct(
        protected ContainerInterface $container,
        protected array $command_map,
    ) {
    }

    /**
     * Resolve a command from the container.
     *
     * @throws CommandNotFoundException
     */
    public function get(string $name): Command
    {
        if (! $this->has($name)) {
            throw new CommandNotFoundException(sprintf('Command "%s" does not exist.', $name));
        }

        return $this->container->get($this->command_map[$name]);
    }

    /**
     * Determines if a command exists in the map (does not require a prior container binding).
     */
    public function has(string $name): bool
    {
        return $name && isset($this->command_map[$name]);
    }

    /**
     * @return list<string>
     */
    public function getNames(): array
    {
        return array_keys($this->command_map);
    }
}
