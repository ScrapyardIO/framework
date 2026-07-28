<?php

namespace Fabricate\Chassis;

use Fabricate\NutsAndBolts\ScrapyardIOException;
use Psr\Container\NotFoundExceptionInterface;

class EntryNotFoundException extends ScrapyardIOException implements NotFoundExceptionInterface
{
    //
}
