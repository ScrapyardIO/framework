<?php

namespace ScrapyardIO\Tests\Fixtures;

use Voyager\Contracts\IOPools\Event;
use Voyager\Contracts\IOPools\MailCollection;
use Voyager\Contracts\IOPools\Receivable;

final class RecordingMailHandler implements Receivable
{
    /** @var list<Event> */
    public array $events = [];

    public function handOff(MailCollection $mail): void
    {
        foreach ($mail->mail() as $event) {
            $this->events[] = $event;
        }
    }
}
