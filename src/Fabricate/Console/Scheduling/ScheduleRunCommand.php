<?php

namespace Fabricate\Console\Scheduling;

use Exception;
use Fabricate\Console\Command;
use Fabricate\Console\Events\ScheduledTaskFailed;
use Fabricate\Console\Events\ScheduledTaskFinished;
use Fabricate\Console\Events\ScheduledTaskSkipped;
use Fabricate\Console\Events\ScheduledTaskStarting;
use Fabricate\Console\WorkshopInstance;
use Fabricate\Contracts\Cache\Repository as Cache;
use Fabricate\Contracts\Debug\ExceptionHandler;
use Fabricate\Contracts\Events\Dispatcher;
use Fabricate\NutsAndBolts\Carbon;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'schedule:run')]
class ScheduleRunCommand extends Command
{
    protected ?string $signature = 'schedule:run {--whisper : Do not output message indicating that no jobs were ready to run}';

    protected string $description = 'Run the scheduled commands';

    protected Schedule $schedule;

    protected Carbon $startedAt;

    protected bool $eventsRan = false;

    protected Dispatcher $dispatcher;

    protected ExceptionHandler $handler;

    protected Cache $cache;

    protected string $phpBinary;

    public function __construct()
    {
        $this->startedAt = Carbon::now();

        parent::__construct();
    }

    public function handle(Schedule $schedule, Dispatcher $dispatcher, Cache $cache, ExceptionHandler $handler): void
    {
        $this->schedule = $schedule;
        $this->dispatcher = $dispatcher;
        $this->cache = $cache;
        $this->handler = $handler;
        $this->phpBinary = WorkshopInstance::phpBinary();

        $events = $this->schedule->dueEvents($this->scrapyard_io);

        if ($events->contains->isRepeatable()) {
            $this->clearInterruptSignal();
        }

        $paused = $this->isPaused();

        foreach ($events as $event) {
            if ($paused && ! $event->runsWhenPaused()) {
                $this->dispatcher->dispatch(new ScheduledTaskSkipped($event));

                continue;
            }

            if (! $event->filtersPass($this->scrapyard_io)) {
                $this->dispatcher->dispatch(new ScheduledTaskSkipped($event));

                continue;
            }

            if (! $this->eventsRan) {
                $this->newLine();
            }

            if ($event->onOneServer) {
                $this->runSingleServerEvent($event);
            } else {
                $this->runEvent($event);
            }

            $this->eventsRan = true;
        }

        if ($events->contains->isRepeatable()) {
            $this->repeatEvents($events->filter->isRepeatable());
        }

        if (! $this->eventsRan) {
            if (! $this->option('whisper')) {
                $this->components->info('No scheduled commands are ready to run.');
            }
        } else {
            $this->newLine();
        }
    }

    protected function runSingleServerEvent($event): void
    {
        if ($this->schedule->serverShouldRun($event, $this->startedAt)) {
            $this->runEvent($event);
        } else {
            $this->components->info(sprintf(
                'Skipping [%s] because the command already ran on another server.', $event->getSummaryForDisplay()
            ));
        }
    }

    protected function runEvent($event): void
    {
        $summary = $event->getSummaryForDisplay();

        $command = $event instanceof CallbackEvent
            ? $summary
            : trim(str_replace($this->phpBinary, '', $event->command));

        $description = sprintf(
            '<fg=gray>%s</> Running [%s]%s',
            Carbon::now()->format('Y-m-d H:i:s'),
            $command,
            $event->runInBackground ? ' in background' : '',
        );

        $this->components->task($description, function () use ($event) {
            $this->dispatcher->dispatch(new ScheduledTaskStarting($event));

            $start = microtime(true);

            try {
                $event->run($this->scrapyard_io);

                $this->dispatcher->dispatch(new ScheduledTaskFinished(
                    $event,
                    round(microtime(true) - $start, 2)
                ));

                $this->eventsRan = true;

                if ($event->exitCode != 0 && ! $event->runInBackground) {
                    throw new Exception("Scheduled command [{$event->command}] failed with exit code [{$event->exitCode}].");
                }
            } catch (Throwable $e) {
                $this->dispatcher->dispatch(new ScheduledTaskFailed($event, $e));

                $this->handler->report($e);
            }

            return $event->exitCode == 0;
        });

        if (! $event instanceof CallbackEvent) {
            $this->components->bulletList([
                $event->getSummaryForDisplay(),
            ]);
        }
    }

    protected function repeatEvents($events): void
    {
        $hasEnteredMaintenanceMode = false;

        $endOfMinute = $this->startedAt->copy()->endOfMinute();

        while (Carbon::now()->lte($endOfMinute)) {
            $paused = $this->isPaused();

            foreach ($events as $event) {
                if ($this->shouldInterrupt()) {
                    return;
                }

                if (! $event->shouldRepeatNow()) {
                    continue;
                }

                if (Carbon::now()->gt($endOfMinute)) {
                    return;
                }

                $hasEnteredMaintenanceMode = $hasEnteredMaintenanceMode || $this->scrapyard_io->isDownForMaintenance();

                if ($hasEnteredMaintenanceMode && ! $event->runsInMaintenanceMode()) {
                    continue;
                }

                if ($paused && ! $event->runsWhenPaused()) {
                    $this->dispatcher->dispatch(new ScheduledTaskSkipped($event));

                    continue;
                }

                if (! $event->filtersPass($this->scrapyard_io)) {
                    $this->dispatcher->dispatch(new ScheduledTaskSkipped($event));

                    continue;
                }

                if ($event->onOneServer) {
                    $this->runSingleServerEvent($event);
                } else {
                    $this->runEvent($event);
                }

                $this->eventsRan = true;
            }

            usleep(100_000);
        }
    }

    protected function isPaused(): bool
    {
        if (! Schedule::$pausable) {
            return false;
        }

        return $this->cache->get(ScheduleSignalKey::PAUSED->value, false);
    }

    protected function shouldInterrupt(): bool
    {
        if (! Schedule::$interruptible) {
            return false;
        }

        return $this->cache->get(ScheduleSignalKey::INTERRUPT->value, false);
    }

    protected function clearInterruptSignal(): void
    {
        $this->cache->forget(ScheduleSignalKey::INTERRUPT->value);
    }
}
