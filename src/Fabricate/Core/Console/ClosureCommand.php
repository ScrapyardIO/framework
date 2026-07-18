<?php

namespace Fabricate\Core\Console;

use Closure;
use Fabricate\Console\Command;
use Fabricate\Console\ManuallyFailedException;
use ReflectionFunction;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClosureCommand extends Command
{
    /**
     * The command callback.
     */
    protected Closure $callback;

    /**
     * The console command description.
     */
    protected string $description = '';

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
            $this->error($e->getMessage());

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
}
