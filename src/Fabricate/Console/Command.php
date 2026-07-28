<?php

namespace Fabricate\Console;

use Fabricate\Console\Attributes\Aliases;
use Fabricate\Console\Attributes\Description;
use Fabricate\Console\Attributes\Help;
use Fabricate\Console\Attributes\Hidden;
use Fabricate\Console\Attributes\Signature;
use Fabricate\Console\Attributes\Usage;
use Fabricate\Console\Concerns\CallsCommands;
use Fabricate\Console\Concerns\ConfiguresPrompts;
use Fabricate\Console\Concerns\HasParameters;
use Fabricate\Console\Concerns\InteractsWithIO;
use Fabricate\Console\Concerns\InteractsWithSignals;
use Fabricate\Console\Concerns\PromptsForMissingInput;
use Fabricate\Console\Contracts\CommandMutex;
use Fabricate\Console\Exceptions\ManuallyFailedException;
use Fabricate\Console\View\Components\Factory as ComponentFactory;
use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Console\Isolatable;
use Fabricate\Contracts\Core\Program;
use Fabricate\NutsAndBolts\Concerns\Macroable;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Command extends SymfonyCommand
{
    use CallsCommands,
        ConfiguresPrompts,
        HasParameters,
        InteractsWithIO,
        InteractsWithSignals,
        PromptsForMissingInput,
        Macroable;
    /**
     * The ScrapyardIO application instance.
     */
    protected Program $scrapyard_io;

    /**
     * The name and signature of the console command.
     */
    protected ?string $signature = null;

    /**
     * The console command name.
     */
    protected string $name = '';

    /**
     * The console command description.
     */
    protected string $description = '';

    /**
     * The console command help text.
     */
    protected string $help = '';

    /**
     * Indicates whether the command should be shown in the command list.
     */
    protected bool $hidden = false;

    /**
     * Indicates whether only one instance of the command can run at any given time.
     */
    protected bool $isolated = false;

    /**
     * The default exit code for isolated commands.
     *
     * @var self::SUCCESS|self::FAILURE|self::INVALID
     */
    protected int $isolated_exit_code = self::SUCCESS;

    /**
     * The console command name aliases.
     *
     * @var string[]
     */
    protected array $aliases;

    /**
     * Create a new console command instance.
     */
    public function __construct()
    {
        $this->configureFromAttributes();

        // We will go ahead and set the name, description, and parameters on console
        // commands just to make things a little easier on the developer. This is
        // so they don't have to all be manually specified in the constructors.
        if (isset($this->signature)) {
            $this->configureUsingFluentDefinition();
        } else {
            parent::__construct($this->name);
        }

        $this->configureUsageFromAttribute();

        // Once we have constructed the command, we'll set the description and other
        // related properties of the command. If a signature wasn't used to build
        // the command we'll set the arguments and the options on this command.
        if (! empty($this->description)) {
            $this->setDescription($this->description);
        }

        if (! empty($this->help)) {
            $this->setHelp($this->help);
        }

        $this->setHidden($this->isHidden());

        if (isset($this->aliases)) {
            $this->setAliases((array) $this->aliases);
        }

        if (! isset($this->signature)) {
            $this->specifyParameters();
        }

        if ($this instanceof Isolatable) {
            $this->configureIsolation();
        }
    }

    /**
     * Configure the command from class attributes.
     */
    protected function configureFromAttributes(): void
    {
        $reflection = new ReflectionClass($this);

        $asCommand = $reflection->getAttributes(AsCommand::class);

        if ($asCommand !== []) {
            $attribute = $asCommand[0]->newInstance();

            if (! is_null($attribute->name) && $attribute->name !== '') {
                $aliases = explode('|', $attribute->name);
                $name = array_shift($aliases);

                if ($name === '') {
                    $this->hidden = true;
                    $name = array_shift($aliases) ?? '';
                }

                $this->name = $name;

                if ($aliases !== []) {
                    $this->aliases = array_values(array_unique(array_merge($this->aliases ?? [], $aliases)));
                }
            }

            if (! is_null($attribute->description) && $attribute->description !== '') {
                $this->description = $attribute->description;
            }

            if (! is_null($attribute->help) && $attribute->help !== '') {
                $this->help = $attribute->help;
            }
        }

        $signature = $reflection->getAttributes(Signature::class);

        if ($signature !== []) {
            $signatureInstance = $signature[0]->newInstance();

            $this->signature = $signatureInstance->signature;

            if ($signatureInstance->aliases !== null) {
                $this->aliases = $signatureInstance->aliases;
            }
        }

        $description = $reflection->getAttributes(Description::class);

        if ($description !== []) {
            $this->description = $description[0]->newInstance()->description;
        }

        $help = $reflection->getAttributes(Help::class);

        if ($help !== []) {
            $this->help = $help[0]->newInstance()->help;
        }

        if ($reflection->getAttributes(Hidden::class) !== []) {
            $this->hidden = true;
        }

        $aliases = $reflection->getAttributes(Aliases::class);

        if ($aliases !== []) {
            $this->aliases = $aliases[0]->newInstance()->aliases;
        }
    }

    /**
     * Configure usage examples for the command from class attributes.
     */
    protected function configureUsageFromAttribute(): void
    {
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getAttributes(Usage::class) as $usage) {
            $this->addUsage($usage->newInstance()->usage);
        }
    }

    /**
     * Configure the console command using a fluent definition.
     */
    protected function configureUsingFluentDefinition(): void
    {
        [$name, $arguments, $options] = Parser::parse($this->signature);

        parent::__construct($this->name = $name);

        // After parsing the signature we will spin through the arguments and options
        // and set them on this command. These will already be changed into proper
        // instances of these "InputArgument" and "InputOption" Symfony classes.
        $this->getDefinition()->addArguments($arguments);
        $this->getDefinition()->addOptions($options);
    }

    /**
     * Configure the console command for isolation.
     */
    protected function configureIsolation(): void
    {
        $this->getDefinition()->addOption(new InputOption(
            'isolated',
            null,
            InputOption::VALUE_OPTIONAL,
            'Do not run the command if another instance of the command is already running',
            $this->isolated
        ));
    }

    /**
     * Run the console command.
     */
    #[\Override]
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output instanceof OutputStyle ? $output : $this->scrapyard_io->make(
            OutputStyle::class, ['input' => $input, 'output' => $output]
        );

        $this->components = $this->scrapyard_io->make(ComponentFactory::class, ['output' => $this->output]);

        $this->configurePrompts($input);

        try {
            return parent::run(
                $this->input = $input, $this->output
            );
        } finally {
            $this->untrap();
        }
    }

    /**
     * Execute the console command.
     * @throws BindingResolutionException
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this instanceof Isolatable && $this->option('isolated') !== false &&
            ! $this->commandIsolationMutex()->create($this)) {
            $this->comment(sprintf(
                'The [%s] command is already running.', $this->getName()
            ));

            return (int) (is_numeric($this->option('isolated'))
                ? $this->option('isolated')
                : $this->isolated_exit_code);
        }

        $method = method_exists($this, 'handle') ? 'handle' : '__invoke';

        try {
            return (int) $this->scrapyard_io->call([$this, $method]);
        } catch (ManuallyFailedException $e) {
            $this->error($e->getMessage());

            return static::FAILURE;
        } finally {
            if ($this instanceof Isolatable && $this->option('isolated') !== false) {
                $this->commandIsolationMutex()->forget($this);
            }
        }
    }

    /**
     * Get a command isolation mutex instance for the command.
     * @throws BindingResolutionException
     */
    protected function commandIsolationMutex(): CommandMutex
    {
        return $this->scrapyard_io->bound(CommandMutex::class)
            ? $this->scrapyard_io->make(CommandMutex::class)
            : $this->scrapyard_io->make(NullCommandMutex::class);
    }

    /**
     * Resolve the console command instance for the given command.
     * @throws BindingResolutionException
     */
    protected function resolveCommand($command): SymfonyCommand
    {
        if (is_string($command)) {
            if (! class_exists($command)) {
                return $this->getApplication()->find($command);
            }

            $command = $this->scrapyard_io->make($command);
        }

        if ($command instanceof SymfonyCommand) {
            $command->setApplication($this->getApplication());
        }

        if ($command instanceof self) {
            $command->setScrapyardIO($this->getScrapyardIO());
        }

        return $command;
    }

    /**
     * Fail the command manually.
     *
     * @param Throwable|string|null $exception
     * @return never
     *
     * @throws Throwable
     */
    public function fail(Throwable|string|null $exception = null): never
    {
        if (is_null($exception)) {
            $exception = 'Command failed manually.';
        }

        if (is_string($exception)) {
            $exception = new ManuallyFailedException($exception);
        }

        throw $exception;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function setHidden(bool $hidden = true): static
    {
        parent::setHidden($this->hidden = $hidden);

        return $this;
    }

    /**
     * Get the ScrapyardIO application instance.
     */
    public function getScrapyardIO(): Program
    {
        return $this->scrapyard_io;
    }

    /**
     * Set the ScrapyardIO application instance.
     */
    public function setScrapyardIO(Program $scrapyard_io): void
    {
        $this->scrapyard_io = $scrapyard_io;
    }
}