<?php

namespace Fabricate\Pipeline;

use Closure;
use Fabricate\Chassis\Contracts\WireframeServiceContainer;
use Fabricate\Contracts\Chassis\BindingResolutionException;
use Fabricate\Contracts\Pipeline\Pipeline as PipelineContract;
use Fabricate\NutsAndBolts\Concerns\Conditionable;
use Fabricate\NutsAndBolts\Concerns\Macroable;
use RuntimeException;
use Throwable;
use UnitEnum;

class Pipeline implements PipelineContract
{
    use Conditionable;
    use Macroable;

    /**
     * The container implementation.
     *
     * @var WireframeServiceContainer|null
     */
    protected ?WireframeServiceContainer $container;

    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected mixed $passable;

    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected array $pipes = [];

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected string $method = 'handle';

    /**
     * The final callback to be executed after the pipeline ends regardless of the outcome.
     *
     * @var Closure|null
     */
    protected ?Closure $finally = null;

    /**
     * Indicates whether to wrap the pipeline in a database transaction.
     *
     * @var string|null|UnitEnum|false
     */
    protected string|null|false|UnitEnum $withinTransaction = false;

    /**
     * Create a new class instance.
     *
     * @param  WireframeServiceContainer|null  $container
     */
    public function __construct(?WireframeServiceContainer $container = null)
    {
        $this->container = $container;
    }

    /**
     * Set the object being sent through the pipeline.
     *
     * @param  mixed  $passable
     * @return $this
     */
    public function send(mixed $passable): static
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the array of pipes.
     *
     * @param  mixed  $pipes
     * @return $this
     */
    public function through(mixed $pipes): static
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    /**
     * Push additional pipes onto the pipeline.
     *
     * @param  mixed  $pipes
     * @return $this
     */
    public function pipe(mixed $pipes): static
    {
        array_push($this->pipes, ...(is_array($pipes) ? $pipes : func_get_args()));

        return $this;
    }

    /**
     * Set the method to call on the pipes.
     *
     * @param string $method
     * @return $this
     */
    public function via(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param Closure $destination
     * @return mixed
     * @throws BindingResolutionException
     */
    public function then(Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes()), $this->carry(), $this->prepareDestination($destination)
        );

        try {
            return $this->withinTransaction !== false
                ? $this->getContainer()->make('db')->connection($this->withinTransaction)->transaction(fn () => $pipeline($this->passable))
                : $pipeline($this->passable);
        } finally {
            if ($this->finally) {
                ($this->finally)($this->passable);
            }
        }
    }

    /**
     * Run the pipeline and return the result.
     *
     * @return mixed
     * @throws BindingResolutionException
     */
    public function thenReturn(): mixed
    {
        return $this->then(function ($passable) {
            return $passable;
        });
    }

    /**
     * Set a final callback to be executed after the pipeline ends regardless of the outcome.
     *
     * @param  Closure  $callback
     * @return $this
     */
    public function finally(Closure $callback): static
    {
        $this->finally = $callback;

        return $this;
    }

    /**
     * Get the final piece of the Closure onion.
     *
     * @param  Closure  $destination
     * @return Closure
     */
    protected function prepareDestination(Closure $destination): Closure
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch (Throwable $e) {
                return $this->handleException($passable, $e);
            }
        };
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return Closure
     */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
                    if (is_callable($pipe)) {
                        // If the pipe is a callable, then we will call it directly, but otherwise we
                        // will resolve the pipes out of the dependency container and call it with
                        // the appropriate method and arguments, returning the results back out.
                        return $pipe($passable, $stack);
                    } elseif (! is_object($pipe)) {
                        [$name, $parameters] = $this->parsePipeString($pipe);

                        // If the pipe is a string we will parse the string and resolve the class out
                        // of the dependency injection container. We can then build a callable and
                        // execute the pipe function giving in the parameters that are required.
                        $pipe = $this->getContainer()->make($name);

                        $parameters = array_merge([$passable, $stack], $parameters);
                    } else {
                        // If the pipe is already an object we'll just make a callable and pass it to
                        // the pipe as-is. There is no need to do any extra parsing and formatting
                        // since the object we're given was already a fully instantiated object.
                        $parameters = [$passable, $stack];
                    }

                    $carry = method_exists($pipe, $this->method)
                        ? $pipe->{$this->method}(...$parameters)
                        : $pipe(...$parameters);

                    return $this->handleCarry($carry);
                } catch (Throwable $e) {
                    return $this->handleException($passable, $e);
                }
            };
        };
    }

    /**
     * Parse full pipe string to get name and parameters.
     *
     * @param  string  $pipe
     * @return array
     */
    protected function parsePipeString($pipe): array
    {
        [$name, $parameters] = array_pad(explode(':', $pipe, 2), 2, null);

        if (! is_null($parameters)) {
            $parameters = explode(',', $parameters);
        } else {
            $parameters = [];
        }

        return [$name, $parameters];
    }

    /**
     * Get the array of configured pipes.
     *
     * @return array
     */
    protected function pipes(): array
    {
        return $this->pipes;
    }

    /**
     * Execute each pipeline step within a database transaction.
     *
     * @param  string|null|UnitEnum|false  $withinTransaction
     * @return $this
     */
    public function withinTransaction($withinTransaction = null): static
    {
        $this->withinTransaction = $withinTransaction;

        return $this;
    }

    /**
     * Get the container instance.
     *
     * @return ?WireframeServiceContainer
     *
     * @throws RuntimeException
     */
    protected function getContainer(): ?WireframeServiceContainer
    {
        if (! $this->container) {
            throw new RuntimeException('A container instance has not been passed to the Pipeline.');
        }

        return $this->container;
    }

    /**
     * Set the container instance.
     *
     * @param  WireframeServiceContainer  $container
     * @return $this
     */
    public function setContainer(WireframeServiceContainer $container): static
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Handle the value returned from each pipe before passing it to the next.
     *
     * @param  mixed  $carry
     * @return mixed
     */
    protected function handleCarry(mixed $carry): mixed
    {
        return $carry;
    }

    /**
     * Handle the given exception.
     *
     * @param  mixed  $passable
     * @param  \Throwable  $e
     * @return mixed
     *
     * @throws \Throwable
     */
    protected function handleException(mixed $passable, Throwable $e): mixed
    {
        throw $e;
    }
}
