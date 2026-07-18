<?php

namespace Fabricate\Queue\Failed;

interface FailedJobProviderInterface
{
    public function log(string $connection, string $queue, string $payload, \Throwable $exception): mixed;

    public function all(): array;

    public function find(mixed $id): mixed;

    public function forget(mixed $id): bool;

    public function flush(int|string|null $hours = null): void;
}
