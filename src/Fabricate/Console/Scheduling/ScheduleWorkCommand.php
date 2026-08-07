<?php

namespace Fabricate\Console\Scheduling;

use Fabricate\Console\Command;
use Fabricate\Console\WorkshopInstance;
use Fabricate\NutsAndBolts\Carbon;
use Fabricate\NutsAndBolts\ProcessUtils;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'schedule:work')]
class ScheduleWorkCommand extends Command
{
    protected ?string $signature = 'schedule:work
        {--run-output-file= : The file to direct <info>schedule:run</info> output to}
        {--whisper : Do not output message indicating that no jobs were ready to run}';

    protected string $description = 'Start the schedule worker';

    /** @var Process[] */
    protected array $executions = [];

    protected bool $shouldQuit = false;

    public function handle(): int
    {
        $this->components->info(
            'Running scheduled tasks.',
            $this->getScrapyardIO()->environment('local') ? OutputInterface::VERBOSITY_NORMAL : OutputInterface::VERBOSITY_VERBOSE
        );

        $command = WorkshopInstance::formatCommandString('schedule:run');

        if ($this->option('whisper')) {
            $command .= ' --whisper';
        }

        if ($this->option('run-output-file')) {
            $command .= ' >> '.ProcessUtils::escapeArgument($this->option('run-output-file')).' 2>&1';
        }

        $this->listenForSignals();

        return $this->work($command);
    }

    protected function work(string $command): int
    {
        $lastExecutionStartedAt = Carbon::now()->subMinutes(10);

        while (true) {
            $this->sleep();

            if (! $this->shouldQuit &&
                Carbon::now()->second === 0 &&
                ! Carbon::now()->startOfMinute()->equalTo($lastExecutionStartedAt)) {
                $this->executions[] = $execution = Process::fromShellCommandline($command, base_path());

                $execution->start();

                $lastExecutionStartedAt = Carbon::now()->startOfMinute();
            }

            foreach ($this->executions as $key => $execution) {
                $output = $execution->getIncrementalOutput().
                    $execution->getIncrementalErrorOutput();

                $this->output->write(ltrim($output, "\n"));

                if (! $execution->isRunning()) {
                    unset($this->executions[$key]);
                }
            }

            if ($this->shouldQuit && empty($this->executions)) {
                return static::SUCCESS;
            }
        }
    }

    protected function listenForSignals(): void
    {
        $this->trap(fn () => [SIGINT, SIGTERM, SIGQUIT], function () {
            $this->shouldQuit = true;
        });
    }

    protected function sleep(): void
    {
        usleep(100 * 1000);
    }
}
