<?php

namespace Fabricate\Contracts\Queue;

interface Queue
{
    public function push(mixed $job, mixed $data = '', ?string $queue = null): mixed;

    public function later(mixed $delay, mixed $job, mixed $data = '', ?string $queue = null): mixed;

    public function bulk(array $jobs, mixed $data = '', ?string $queue = null): void;
}
