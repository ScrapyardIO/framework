<?php

namespace Fabricate\Console;

use Closure;
use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Core\Machine;
use Fabricate\NutsAndBolts\ProcessUtils;
use ReflectionClass;
use Fabricate\Contracts\Chassis\Chassis;
use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\Console\Events\WorkshopStarting;
use ReflectionException;
use Fabricate\Console\ContainerCommandLoader;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Fabricate\Contracts\Console\ConsoleMachine as MachineContract;

use Symfony\Component\Console\Output\OutputInterface;
use function Fabricate\NutsAndBolts\php_binary;
use function Fabricate\NutsAndBolts\workshop_binary;

class ConsoleMachine extends SymfonyApplication implements MachineContract
{
    /**
     * The ScrapyardIO application instance.
     *
     * @var Machine
     */
    protected Machine $scrapyard_io;

    /**
     * The event dispatcher instance.
     *
     * @var Dispatcher
     */
    protected Dispatcher $events;

    /**
     * The output from the previous command.
     *
     * @var BufferedOutput
     */
    protected BufferedOutput $lastOutput;

    /**
     * The console application bootstrappers.
     *
     * @var array<array-key, \Closure($this): void>
     */
    protected static array $bootstrappers = [];

    /**
     * A map of command names to classes.
     *
     * @var array<string, Command|string>
     */
    protected array $commandMap = [];

    /**
     * Create a new Artisan console application.
     *
     * @param Machine $scrapyard_io
     * @param ?Dispatcher $events
     * @param  string  $version
     */
    public function __construct(Machine $scrapyard_io, ?Dispatcher $events, $version)
    {
        parent::__construct('ScrapyardIO Framework', $version);

        $this->scrapyard_io = $scrapyard_io;
        if($events) {
            $this->events = $events;
        }
        $this->setAutoExit(false);
        $this->setCatchExceptions(false);

        if($events) {
            $this->events->dispatch(new WorkshopStarting($this));
        }

        $this->bootstrap();
    }

    /**
     * Determine the proper PHP executable.
     *
     * @return string
     */
    public static function phpBinary(): string
    {
        return ProcessUtils::escapeArgument(php_binary());
    }

    /**
     * Determine the proper Artisan executable.
     *
     * @return string
     */
    public static function workshopBinary(): string
    {
        return ProcessUtils::escapeArgument(workshop_binary());
    }

    /**
     * Format the given command as a fully-qualified executable command.
     *
     * @param string $string
     * @return string
     */
    public static function formatCommandString(string $string): string
    {
        return sprintf('%s %s %s', static::phpBinary(), static::workshopBinary(), $string);
    }

    /**
     * Run an Artisan console command by name.
     *
     * @param  SymfonyCommand|string  $command
     * @param  array  $parameters
     * @param OutputInterface|null $outputBuffer
     * @return int
     *
     * @throws CommandNotFoundException|\Exception
     */
    public function call($command, array $parameters = [], ?OutputInterface $outputBuffer = null): int
    {
        [$command, $input] = $this->parseCommand($command, $parameters);

        if (! $this->has($command)) {
            throw new CommandNotFoundException(sprintf('The command "%s" does not exist.', $command));
        }

        return $this->run(
            $input, $this->lastOutput = $outputBuffer ?: new BufferedOutput
        );
    }

    /**
     * Parse the incoming Artisan command and its input.
     *
     * @param string|SymfonyCommand $command
     * @param array $parameters
     * @return array
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
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output(): string
    {
        return $this->lastOutput && method_exists($this->lastOutput, 'fetch')
            ? $this->lastOutput->fetch()
            : '';
    }

    /**
     * Alias for addCommand() since Symfony's add() method was deprecated.
     *
     * @param  \Symfony\Component\Console\Command\Command  $command
     * @return \Symfony\Component\Console\Command\Command|null
     */
    public function add(SymfonyCommand $command): ?SymfonyCommand
    {
        return $this->addCommand($command);
    }

    /**
     * Add a command to the console.
     *
     * @param  \Symfony\Component\Console\Command\Command|callable  $command
     * @return \Symfony\Component\Console\Command\Command|null
     */
    public function addCommand(SymfonyCommand|callable $command): ?SymfonyCommand
    {
        if ($command instanceof Command) {
            $command->setScrapyardIO($this->scrapyard_io);
        }

        return $this->addToParent($command);
    }

    /**
     * Add the command to the parent instance.
     *
     * @param  SymfonyCommand|callable  $command
     * @return SymfonyCommand
     */
    protected function addToParent(SymfonyCommand|callable $command): SymfonyCommand
    {
        return parent::addCommand($command);
    }

    /**
     * Add a command, resolving through the application.
     *
     * @param string|Command $command
     * @return SymfonyCommand|null
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function resolve(string|Command $command): ?SymfonyCommand
    {
        if (is_subclass_of($command, SymfonyCommand::class)) {
            $attribute = new ReflectionClass($command)->getAttributes(AsCommand::class);

            $commandName = ! empty($attribute) ? $attribute[0]->newInstance()->name : null;

            if (! is_null($commandName)) {
                foreach (explode('|', $commandName) as $name) {
                    $this->commandMap[$name] = $command;
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
     *
     * @param  mixed  $commands
     * @return $this
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
     * Set the container command loader for lazy resolution.
     *
     * @return $this
     */
    public function setContainerCommandLoader(): static
    {
        $this->setCommandLoader(new ContainerCommandLoader($this->scrapyard_io, $this->commandMap));

        return $this;
    }

    /**
     * Get the default input definition for the application.
     *
     * This is used to add the --env option to every available command.
     *
     * @return InputDefinition
     */
    #[\Override]
    protected function getDefaultInputDefinition(): InputDefinition
    {
        return tap(parent::getDefaultInputDefinition(), function ($definition) {
            $definition->addOption($this->getEnvironmentOption());
        });
    }

    /**
     * Get the global environment option for the definition.
     *
     * @return InputOption
     */
    protected function getEnvironmentOption(): InputOption
    {
        $message = 'The environment the command should run under';

        return new InputOption('--env', null, InputOption::VALUE_OPTIONAL, $message);
    }

    /**
     * Get the ScrapyardIO application instance.
     */
    public function getScrapyardIO(): Chassis
    {
        return $this->scrapyard_io;
    }


    /**
     * Bootstrap the console application.
     *
     * @return void
     */
    protected function bootstrap(): void
    {
        foreach (static::$bootstrappers as $bootstrapper) {
            $bootstrapper($this);
        }
    }

    /**
     * Register a console "starting" bootstrapper.
     *
     * @param  Closure($this): void  $callback
     * @return void
     */
    public static function starting(Closure $callback): void
    {
        static::$bootstrappers[] = $callback;
    }

    /**
     * Clear the console application bootstrappers.
     *
     * @return void
     */
    public static function forgetBootstrappers(): void
    {
        static::$bootstrappers = [];
    }

}
