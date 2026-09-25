<?php

namespace ScrapyardIO\Tests\Fixtures;

use Voyager\Contracts\IOPools\WorkTarget;

/** Stands in for IOPools' WorkTargetManager: every name gets the same target. */
final class FakeWorkTargets
{
    public function __construct(
        private readonly WorkTarget $target,
    ) {}

    public function driver(?string $name = null): WorkTarget
    {
        return $this->target;
    }
}
