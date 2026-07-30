<?php

namespace Fabricate\Contracts\Actuation\HumanInput;

enum CoordinateSpace: string
{
    case NORMALIZED = 'normalized';
    case PIXELS = 'pixels';
}
