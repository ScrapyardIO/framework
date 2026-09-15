<?php

namespace GeneralPurposeIO\Contracts\Core\Mail;

use Throwable;
use Voyager\Contracts\IOPools\Completion;

/** Result of deferred work. Named gpio.transfer.<transfer>. ok() is "the closure did not throw". */
final class TransferCompletion implements Completion
{
    public readonly string $name;

    public function __construct(
        public readonly string $transfer,
        public readonly mixed $result = null,
        public readonly ?Throwable $error = null,
    ) {
        $this->name = "gpio.transfer.{$transfer}";
    }

    public function ok(): bool
    {
        return is_null($this->error);
    }
}
