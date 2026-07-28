<?php

namespace Fabricate\Contracts\Sketches;

enum SketchLoopResult: string
{
    case CONTINUE = 'continue';
    case STOP = 'stop';
}
