<?php

namespace Fabricate\Contracts\Actuation\HumanInput;

enum GameControllerAxis: string
{
    case LEFT_X = 'left_x';
    case LEFT_Y = 'left_y';
    case RIGHT_X = 'right_x';
    case RIGHT_Y = 'right_y';
    case LEFT_TRIGGER = 'left_trigger';
    case RIGHT_TRIGGER = 'right_trigger';
}
