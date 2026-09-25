<?php

namespace ScrapyardIO\Tests\Fixtures;

/** A fake whose slaves share one queue per device, the way an MPSSE bridge's do. */
final class FakeBridgeI2CConnectionDriver extends FakeI2CConnectionDriver
{
    protected function queueKey(string|int $device, int $address): string
    {
        return (string) $device;
    }
}
