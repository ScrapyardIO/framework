<?php

namespace Fabricate\Chassis\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use Fabricate\Contracts\Chassis\ChassisException;

class CircularDependencyException extends ChassisException implements ContainerExceptionInterface
{
    //
}