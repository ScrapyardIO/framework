<?php

namespace Fabricate\Contracts\Sketches;

enum SketchExitStatus: int
{
    case SUCCESS = 0;
    case FAILURE = 1;
}
