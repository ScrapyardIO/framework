<?php

namespace GeneralPurposeIO\Contracts\Digital;

enum SignalEdge: string
{
    case RISING = 'rising';
    case FALLING = 'falling';
}
