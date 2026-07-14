<?php

namespace BareMetal\Contracts\Chassis;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class CircularDependencyException extends Exception implements ContainerExceptionInterface
{

}
