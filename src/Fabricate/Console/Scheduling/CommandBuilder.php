<?php

namespace Fabricate\Console\Scheduling;

use Fabricate\Console\WorkshopInstance;
use Fabricate\NutsAndBolts\ProcessUtils;

class CommandBuilder
{
    /**
     * Build the command for the given event.
     */
    public function buildCommand(Event $event): string
    {
        if ($event->runInBackground) {
            return $this->buildBackgroundCommand($event);
        }

        return $this->buildForegroundCommand($event);
    }

    /**
     * Build the command for running the event in the foreground.
     */
    protected function buildForegroundCommand(Event $event): string
    {
        $output = ProcessUtils::escapeArgument($event->output);

        return $this->ensureCorrectUser(
            $event,
            $event->command.($event->shouldAppendOutput ? ' >> ' : ' > ').$output.' 2>&1'
        );
    }

    /**
     * Build the command for running the event in the background.
     */
    protected function buildBackgroundCommand(Event $event): string
    {
        $output = ProcessUtils::escapeArgument($event->output);

        $redirect = $event->shouldAppendOutput ? ' >> ' : ' > ';

        $finished = WorkshopInstance::formatCommandString('schedule:finish').' "'.$event->mutexName().'"';

        if (windows_os()) {
            return 'start /b cmd /v:on /c "('.$event->command.' & '.$finished.' ^!ERRORLEVEL^!)'.$redirect.$output.' 2>&1"';
        }

        return $this->ensureCorrectUser(
            $event,
            '('.$event->command.$redirect.$output.' 2>&1 ; '.$finished.' "$?") > '
            .ProcessUtils::escapeArgument($event->getDefaultOutput()).' 2>&1 &'
        );
    }

    /**
     * Finalize the event's command syntax with the correct user.
     */
    protected function ensureCorrectUser(Event $event, string $command): string
    {
        return $event->user && ! windows_os()
            ? 'sudo -u '.$event->user.' -- sh -c '.ProcessUtils::escapeArgument($command)
            : $command;
    }
}
