<?php

namespace BareMetal\Console;

use BareMetal\Console\Events\WorkshopStarting;
use Exception;
use ReflectionClass;
use ReflectionException;
use BareMetal\Contracts\Events\Dispatcher;
use BareMetal\Contracts\Console\ConsoleMachine;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use BareMetal\Contracts\Core\Machine as MachineInterface;
use BareMetal\Contracts\Chassis\BindingResolutionException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Exception\CommandNotFoundException;

class Machine extends SymfonyApplication implements ConsoleMachine
{
    /**
     * The ScrapyardIO application instance.
     */
    protected MachineInterface $scrapyard_io;

    /**
     * The event dispatcher instance.
     */
    protected Dispatcher $events;

    /**
     * The output from the previous command.
     */
    protected BufferedOutput $last_output;

    /**
     * The console application bootstrappers.
     */
    protected static array $bootstrappers = [];

    /**
     * A map of command names to classes.
     */
    protected array $command_map = [];

    public function __construct(
        MachineInterface $scrapyard_io,
        Dispatcher $events,
        string $version
    ) {
        parent::__construct('The Powerful and Mysterious ScrapyardIO Workshop', $version);

        $this->scrapyard_io = $scrapyard_io;
        $this->events = $events;
        $this->setAutoExit(false);
        $this->setCatchExceptions(false);

        $this->events->dispatch(new WorkshopStarting($this));

        $this->bootstrap();
    }

    /**
     * Run a Workshop console command by name.
     *
     * @throws CommandNotFoundException
     * @throws Exception
     */
    public function call(SymfonyCommand|string $command, array $parameters = [], ?OutputInterface $output_buffer = null): int
    {
        [$command, $input] = $this->parseCommand($command, $parameters);

        if (! $this->has($command)) {
            throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $command));
        }

        return $this->run(
            $input, $this->last_output = $output_buffer ?: new BufferedOutput
        );
    }

    /**
     * Get the output for the last run command.
     */
    public function output(): string
    {
        return $this->last_output && method_exists($this->last_output, 'fetch')
            ? $this->last_output->fetch()
            : '';
    }

    /**
     * Alias for addCommand() since Symfony's add() method was deprecated.
     */
    public function add(SymfonyCommand $command): ?SymfonyCommand
    {
        return $this->addCommand($command);
    }

    /**
     * Add a command to the console.
     */
    public function addCommand(SymfonyCommand|callable $command): ?SymfonyCommand
    {
        if ($command instanceof Command) {
            $command->setScrapyardIO($this->scrapyard_io);
        }

        return $this->addToParent($command);
    }

    /**
     * Add a command, resolving through the application.
     * @throws ReflectionException|BindingResolutionException
     */
    public function resolve(Command|string $command): ?SymfonyCommand
    {
        if (is_subclass_of($command, SymfonyCommand::class)) {
            $attribute = (new ReflectionClass($command))->getAttributes(AsCommand::class);

            $commandName = ! empty($attribute) ? $attribute[0]->newInstance()->name : null;

            if (! is_null($commandName)) {
                foreach (explode('|', $commandName) as $name) {
                    $this->command_map[$name] = $command;
                }

                return null;
            }
        }

        if ($command instanceof Command) {
            return $this->addCommand($command);
        }

        return $this->addCommand($this->scrapyard_io->make($command));
    }

    /**
     * Resolve an array of commands through the application.
     * @throws BindingResolutionException|ReflectionException
     */
    public function resolveCommands(mixed $commands): static
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        foreach ($commands as $command) {
            $this->resolve($command);
        }

        return $this;
    }

    /**
     * Add the command to the parent instance.
     */
    protected function addToParent(SymfonyCommand|callable $command): SymfonyCommand
    {
        return parent::addCommand($command);
    }

    /**
     * Set the container command loader for lazy resolution.
     */
    public function setContainerCommandLoader(): static
    {
        $this->setCommandLoader(new ContainerCommandLoader($this->scrapyard_io, $this->command_map));

        return $this;
    }

    /**
     * Bootstrap the console application.
     */
    protected function bootstrap(): void
    {
        foreach (static::$bootstrappers as $bootstrapper) {
            $bootstrapper($this);
        }
    }

    /**
     * Parse the incoming Workshop command and its input.
     *
     * @return array{0: string|null, 1: \Symfony\Component\Console\Input\InputInterface}
     *
     * @throws BindingResolutionException
     */
    protected function parseCommand(SymfonyCommand|string $command, array $parameters): array
    {
        if (is_subclass_of($command, SymfonyCommand::class)) {
            $callingClass = true;

            if (is_object($command)) {
                $command = get_class($command);
            }

            $command = $this->scrapyard_io->make($command)->getName();
        }

        if (! isset($callingClass) && empty($parameters)) {
            $command = $this->getCommandName($input = new StringInput($command));
        } else {
            array_unshift($parameters, $command);

            $input = new ArrayInput($parameters);
        }

        return [$command, $input];
    }

    /**
     * Register a console "starting" bootstrapper.
     */
    public static function starting(callable $callback): void
    {
        static::$bootstrappers[] = $callback;
    }

    /**
     * Clear the console application bootstrappers.
     */
    public static function forgetBootstrappers(): void
    {
        static::$bootstrappers = [];
    }
}
