<?php

namespace Fabricate\Console\Scheduling;

use Closure;
use Cron\CronExpression;
use DateTimeZone;
use Fabricate\Console\Command;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Carbon;
use Fabricate\NutsAndBolts\Collection;
use ReflectionClass;
use ReflectionFunction;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Terminal;

#[AsCommand(name: 'schedule:list')]
class ScheduleListCommand extends Command
{
    protected ?string $signature = 'schedule:list
        {--timezone= : The timezone that times should be displayed in}
        {--environment=* : Display the tasks scheduled to run on this environment}
        {--next : Sort the listed tasks by their next due date}
        {--json : Output the scheduled tasks as JSON}
    ';

    protected string $description = 'List all scheduled tasks';

    protected static ?Closure $terminalWidthResolver = null;

    public function handle(Schedule $schedule): void
    {
        $environments = Arr::wrap($this->option('environment'));

        $events = new Collection(
            empty($environments)
                ? $schedule->events()
                : $schedule->eventsForEnvironments($environments)
        );

        if ($events->isEmpty()) {
            if ($this->option('json')) {
                $this->output->writeln('[]');
            } else {
                $this->components->info('No scheduled tasks have been defined.');
            }

            return;
        }

        $timezone = new DateTimeZone($this->option('timezone') ?? config('machine.timezone', 'UTC'));

        $events = $this->sortEvents($events, $timezone);

        $this->display($events, $timezone);
    }

    protected function displayJson(Collection $events, DateTimeZone $timezone): void
    {
        $this->output->writeln($events->flatMap(function ($event) use ($timezone) {
            $nextDueDate = $this->getNextDueDateForEvent($event, $timezone);

            $command = $event->command ?? '';

            if (! $this->output->isVerbose()) {
                $command = $event->normalizeCommand($command);
            }

            if ($event instanceof CallbackEvent) {
                $command = $event->getSummaryForDisplay();

                if (in_array($command, ['Closure', 'Callback'], true)) {
                    $command = 'Closure at: '.$this->getClosureLocation($event);
                }
            }

            return (new Collection(CronExpressionTimezoneConverter::forEvent($event, $timezone)))->map(fn ($expression) => [
                'expression' => $expression,
                'command' => $command,
                'description' => $event->description ?? null,
                'next_due_date' => $nextDueDate->format('Y-m-d H:i:s P'),
                'next_due_date_human' => $nextDueDate->diffForHumans(),
                'timezone' => $timezone->getName(),
                'has_mutex' => $event->mutex->exists($event),
                'repeat_seconds' => $event->isRepeatable() ? $event->repeatSeconds : null,
                'environments' => $event->environments,
            ]);
        })->values()->toJson());
    }

    protected function displayForCli(Collection $events, DateTimeZone $timezone): void
    {
        $terminalWidth = self::getTerminalWidth();

        $expressionSpacing = $this->getCronExpressionSpacing($events, $timezone);

        $repeatExpressionSpacing = $this->getRepeatExpressionSpacing($events);

        $events = $events->flatMap(function ($event) use ($terminalWidth, $expressionSpacing, $repeatExpressionSpacing, $timezone) {
            return (new Collection(CronExpressionTimezoneConverter::forEvent($event, $timezone)))->map(
                fn ($expression) => $this->listEvent($event, $terminalWidth, $expressionSpacing, $repeatExpressionSpacing, $timezone, $expression),
            );
        });

        $this->line(
            $events->flatten()->filter()->prepend('')->push('')->toArray(),
        );
    }

    /**
     * @return array<int, int>
     */
    private function getCronExpressionSpacing(Collection $events, DateTimeZone $timezone): array
    {
        $rows = $events->flatMap(fn ($event) => (new Collection(CronExpressionTimezoneConverter::forEvent($event, $timezone)))
            ->map(fn ($expression) => array_map(mb_strlen(...), preg_split("/\s+/", $expression))));

        return (new Collection($rows[0] ?? []))->keys()->map(fn ($key) => $rows->max($key))->all();
    }

    private function getRepeatExpressionSpacing(Collection $events): int
    {
        return $events->map(fn ($event) => mb_strlen($this->getRepeatExpression($event)))->max();
    }

    /**
     * @param  array<int, int>  $expressionSpacing
     * @return array<int, string>
     */
    private function listEvent($event, int $terminalWidth, array $expressionSpacing, int $repeatExpressionSpacing, DateTimeZone $timezone, ?string $convertedExpression = null): array
    {
        $expression = $this->formatCronExpression($convertedExpression ?? $event->expression, $expressionSpacing);

        $repeatExpression = str_pad($this->getRepeatExpression($event), $repeatExpressionSpacing);

        $command = $event->command ?? '';

        $description = $event->description ?? '';

        if (! $this->output->isVerbose()) {
            $command = $event->normalizeCommand($command);
        }

        if ($event instanceof CallbackEvent) {
            $command = $event->getSummaryForDisplay();

            if (in_array($command, ['Closure', 'Callback'], true)) {
                $command = 'Closure at: '.$this->getClosureLocation($event);
            }
        }

        $command = mb_strlen($command) > 1 ? "{$command} " : '';

        $nextDueDateLabel = 'Next Due:';

        $nextDueDate = $this->getNextDueDateForEvent($event, $timezone);

        $nextDueDate = $this->output->isVerbose()
            ? $nextDueDate->format('Y-m-d H:i:s P')
            : $nextDueDate->diffForHumans();

        $hasMutex = $event->mutex->exists($event) ? 'Has Mutex › ' : '';

        $dots = str_repeat('.', max(
            $terminalWidth - mb_strwidth($expression.$repeatExpression.$command.$nextDueDateLabel.$nextDueDate.$hasMutex) - 8, 0,
        ));

        $command = preg_replace("#(php [\w\-./]+workshop [\w\-:]+) (.+)#", '$1 <fg=yellow;options=bold>$2</>', $command);

        return [sprintf(
            '  <fg=yellow>%s</> <fg=#6C7280>%s</> %s<fg=#6C7280>%s %s%s %s</>',
            $expression,
            $repeatExpression,
            $command,
            $dots,
            $hasMutex,
            $nextDueDateLabel,
            $nextDueDate
        ), $this->output->isVerbose() && mb_strlen($description) > 1 ? sprintf(
            '  <fg=#6C7280>%s%s %s</>',
            str_repeat(' ', mb_strlen($expression) + 2),
            '⇁',
            $description
        ) : ''];
    }

    private function getRepeatExpression($event): string
    {
        return $event->isRepeatable() ? "{$event->repeatSeconds}s " : '';
    }

    private function sortEvents(Collection $events, DateTimeZone $timezone): Collection
    {
        return $this->option('next')
            ? $events->sortBy(fn ($event) => $this->getNextDueDateForEvent($event, $timezone))
            : $events;
    }

    protected function display(Collection $events, DateTimeZone $timezone): void
    {
        $this->option('json') ? $this->displayJson($events, $timezone) : $this->displayForCli($events, $timezone);
    }

    private function getNextDueDateForEvent($event, DateTimeZone $timezone): Carbon
    {
        $nextDueDate = Carbon::instance(
            (new CronExpression($event->expression))
                ->getNextRunDate(Carbon::now()->setTimezone($event->timezone))
                ->setTimezone($timezone),
        );

        if (! $event->isRepeatable()) {
            return $nextDueDate;
        }

        $previousDueDate = Carbon::instance(
            (new CronExpression($event->expression))
                ->getPreviousRunDate(Carbon::now()->setTimezone($event->timezone), allowCurrentDate: true)
                ->setTimezone($timezone),
        );

        $now = Carbon::now()->setTimezone($event->timezone);

        if (! $now->copy()->startOfMinute()->eq($previousDueDate)) {
            return $nextDueDate;
        }

        return $now
            ->endOfSecond()
            ->ceilSeconds($event->repeatSeconds);
    }

    /**
     * @param  array<int, int>  $spacing
     */
    private function formatCronExpression(string $expression, array $spacing): string
    {
        $expressions = preg_split("/\s+/", $expression);

        return (new Collection($spacing))
            ->map(fn ($length, $index) => str_pad($expressions[$index], $length))
            ->implode(' ');
    }

    private function getClosureLocation(CallbackEvent $event): string
    {
        $callback = (new ReflectionClass($event))->getProperty('callback')->getValue($event);

        if ($callback instanceof Closure) {
            $function = new ReflectionFunction($callback);

            return sprintf(
                '%s:%s',
                str_replace($this->scrapyard_io->basePath().DIRECTORY_SEPARATOR, '', $function->getFileName() ?: ''),
                $function->getStartLine()
            );
        }

        if (is_string($callback)) {
            return $callback;
        }

        if (is_array($callback)) {
            $className = is_string($callback[0]) ? $callback[0] : $callback[0]::class;

            return sprintf('%s::%s', $className, $callback[1]);
        }

        return sprintf('%s::__invoke', $callback::class);
    }

    public static function getTerminalWidth(): int
    {
        return is_null(static::$terminalWidthResolver)
            ? (new Terminal)->getWidth()
            : call_user_func(static::$terminalWidthResolver);
    }

    public static function resolveTerminalWidthUsing(?Closure $resolver): void
    {
        static::$terminalWidthResolver = $resolver;
    }
}
