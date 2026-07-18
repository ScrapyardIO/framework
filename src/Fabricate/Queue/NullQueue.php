<?php

namespace Fabricate\Queue;

use Fabricate\Contracts\Queue\Queue;

class NullQueue implements Queue
{
    public function push(mixed $job, mixed $data = '', ?string $queue = null): mixed
    {
        return null;
    }

    public function later(mixed $delay, mixed $job, mixed $data = '', ?string $queue = null): mixed
    {
        return null;
    }

    public function bulk(array $jobs, mixed $data = '', ?string $queue = null): void
    {
        //
    }
}
