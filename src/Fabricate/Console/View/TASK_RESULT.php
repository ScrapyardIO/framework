<?php

namespace Fabricate\Console\View;

enum TASK_RESULT: int
{
    case SUCCESS = 1;
    case FAILURE = 2;
    case SKIPPED = 3;
}
