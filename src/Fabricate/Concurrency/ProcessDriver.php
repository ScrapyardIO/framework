<?php

namespace Fabricate\Concurrency;

use Carbon\CarbonInterval;
use Closure;
use Exception;
use Fabricate\Console\WorkshopInstance;
use Fabricate\Contracts\Concurrency\Driver;
use Fabricate\Process\Factory as ProcessFactory;
use Fabricate\Process\Pool;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Defer\DeferredCallback;
use Laravel\SerializableClosure\SerializableClosure;

use function Fabricate\Core\Helpers\defer;

class ProcessDriver implements Driver
{
    /**
     * Create a new process based concurrency driver.
     */
    public function __construct(protected ProcessFactory $processFactory)
    {
        //
    }

    /**
     * Run the given tasks concurrently and return an array containing the results.
     *
     * @throws \Throwable
     */
    public function run(Closure|array $tasks, CarbonInterval|int|null $timeout = null): array
    {
        $command = WorkshopInstance::formatCommandString('invoke-serialized-closure');

        $results = $this->processFactory->pool(function (Pool $pool) use ($tasks, $command, $timeout) {
            foreach (Arr::wrap($tasks) as $key => $task) {
                $process = $pool->as($key)->path(base_path())->env([
                    'SCRAPYARD_IO_INVOKABLE_CLOSURE' => base64_encode(
                        serialize(new SerializableClosure($task))
                    ),
                ])->command($command);

                if (! is_null($timeout)) {
                    $process->timeout($timeout);
                }
            }
        })->start()->wait();

        return $results->collect()->mapWithKeys(function ($result, $key) {
            if ($result->failed()) {
                throw new Exception('Concurrent process failed with exit code ['.$result->exitCode().']. Message: '.$result->errorOutput());
            }

            $output = $result->output();

            if (($pos = strpos($output, "\x1f\x8b")) !== false) {
                $output = substr($output, 0, $pos);
            }

            $result = json_decode($output, true);

            if (! $result['successful']) {
                throw new $result['exception'](
                    ...(! empty(array_filter($result['parameters'], fn ($parameter) => ! is_null($parameter)))
                        ? $result['parameters']
                        : [$result['message']])
                );
            }

            return [$key => unserialize($result['result'])];
        })->all();
    }

    /**
     * Start the given tasks in the background after the current task has finished.
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        $command = WorkshopInstance::formatCommandString('invoke-serialized-closure');

        return defer(function () use ($tasks, $command) {
            foreach (Arr::wrap($tasks) as $task) {
                $this->processFactory->path(base_path())->env([
                    'SCRAPYARD_IO_INVOKABLE_CLOSURE' => base64_encode(
                        serialize(new SerializableClosure($task))
                    ),
                ])->run($command.' 2>&1 &');
            }
        });
    }
}
