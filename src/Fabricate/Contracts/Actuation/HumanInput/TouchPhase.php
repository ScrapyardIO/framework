<?php

namespace Fabricate\Contracts\Actuation\HumanInput;

enum TouchPhase: string
{
    case BEGAN = 'began';
    case MOVED = 'moved';
    case STATIONARY = 'stationary';
    case ENDED = 'ended';
    case CANCELLED = 'cancelled';
}
