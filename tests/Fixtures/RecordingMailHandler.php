<?php

namespace ScrapyardIO\Tests\Fixtures;

use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\MailHandler;

/** Keeps every piece of loop mail it is handed, in arrival order. */
final class RecordingMailHandler implements MailHandler
{
    /** @var list<object> */
    public array $events = [];

    public function handOff(array $mail, Loop $loop): void
    {
        foreach ($mail as $event) {
            $this->events[] = $event;
        }
    }
}
