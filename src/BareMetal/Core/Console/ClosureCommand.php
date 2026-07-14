<?php

namespace BareMetal\Core\Console;

use BareMetal\Console\Command;
use BareMetal\Console\ManuallyFailedException;
use Closure;
use ReflectionFunction;
use RuntimeException;
use ScrapyardIO\NutsAndBolts\Concerns\ForwardsCalls;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClosureCommand extends Command
{
    use ForwardsCalls;

    /**
     * The command callback.
     */
    protected Closure $callback;

    /**
     * The console command description.
     */
    protected $description = '';

    /**
     * Create a new command instance.
     */
    public function __construct(string $signature, Closure $callback)
    {
        $this->callback = $callback;
        $this->signature = $signature;

        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputs = array_merge($input->getArguments(), $input->getOptions());

        $parameters = [];

        foreach ((new ReflectionFunction($this->callback))->getParameters() as $parameter) {
            if (isset($inputs[$parameter->getName()])) {
                $parameters[$parameter->getName()] = $inputs[$parameter->getName()];
            }
        }

        try {
            return (int) $this->scrapyard_io->call(
                $this->callback->bindTo($this, $this), $parameters
            );
        } catch (ManuallyFailedException $e) {
            $this->components->error($e->getMessage());

            return static::FAILURE;
        }
    }

    /**
     * Set the description for the command.
     */
    public function purpose(string $description): static
    {
        return $this->describe($description);
    }

    /**
     * Set the description for the command.
     */
    public function describe(string $description): static
    {
        $this->setDescription($description);

        return $this;
    }

    /**
     * Create a new scheduled event for the command.
     *
     * @throws RuntimeException
     */
    public function schedule(array $parameters = []): never
    {
        // Laravel forwards to Schedule::command($this->name, $parameters).
        // Port BareMetal Console Scheduling before enabling this path.
        throw new RuntimeException(
            'Command scheduling has not been ported yet.'
        );
    }

    /**
     * Dynamically proxy calls to a new scheduled event.
     *
     * @throws RuntimeException
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->schedule(), $method, $parameters);
    }
}
