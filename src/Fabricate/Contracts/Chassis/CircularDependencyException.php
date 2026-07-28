<?php

namespace Fabricate\Contracts\Chassis;

use Psr\Container\ContainerExceptionInterface;
use Fabricate\NutsAndBolts\ScrapyardIOException;

class CircularDependencyException extends ScrapyardIOException implements ContainerExceptionInterface
{
    //
}