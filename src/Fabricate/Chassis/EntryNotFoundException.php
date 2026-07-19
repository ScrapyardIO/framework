<?php

namespace Fabricate\Chassis;

use Psr\Container\NotFoundExceptionInterface;
use Fabricate\NutsAndBolts\ScrapyardIOException;

class EntryNotFoundException extends ScrapyardIOException implements NotFoundExceptionInterface
{
    //
}
