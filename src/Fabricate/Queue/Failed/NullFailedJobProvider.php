<?php

namespace Fabricate\Queue\Failed;

class NullFailedJobProvider implements FailedJobProviderInterface
{
    public function log(string $connection, string $queue, string $payload, \Throwable $exception): mixed
    {
        return null;
    }

    public function all(): array
    {
        return [];
    }

    public function find(mixed $id): mixed
    {
        return null;
    }

    public function forget(mixed $id): bool
    {
        return false;
    }

    public function flush(int|string|null $hours = null): void
    {
        //
    }
}
