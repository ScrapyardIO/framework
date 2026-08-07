<?php

namespace Fabricate\Validation;

use RuntimeException;

/**
 * Placeholder until fabricate/database restores connection resolution.
 *
 * Core does not register this verifier — presence checks require an app-bound
 * {@see PresenceVerifierInterface} implementation (typically from database).
 */
class DatabasePresenceVerifier implements DatabasePresenceVerifierInterface
{
    /**
     * @return never
     */
    protected function unavailable(): void
    {
        throw new RuntimeException(
            'Database presence verification requires fabricate/database. Bind validation.presence with a PresenceVerifierInterface implementation.'
        );
    }

    public function getCount($collection, $column, $value, $excludeId = null, $idColumn = null, array $extra = [])
    {
        $this->unavailable();
    }

    public function getMultiCount($collection, $column, array $values, array $extra = [])
    {
        $this->unavailable();
    }

    public function setConnection($connection): void
    {
        //
    }
}
