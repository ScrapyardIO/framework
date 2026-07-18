<?php

namespace GeneralPurposeIO\Contracts\Common;

use Fabricate\NutsAndBolts\ScrapyardIOException;

class GPIOException extends ScrapyardIOException
{
    public static function carrierFactoryNotImplemented(string $class_name): static
    {
        return new static("{$class_name} required a CarrierFactory Attribute.");
    }

    public static function unsupportedDriverProtocol(string $protocol, string $library): static
    {
        return new static("{$library} does not support {$protocol}.");
    }
}
