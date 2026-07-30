<?php

namespace Fabricate\Contracts\Actuation\HumanInput;

enum GameControllerButton: string
{
    case SOUTH = 'south';
    case EAST = 'east';
    case WEST = 'west';
    case NORTH = 'north';
    case BACK = 'back';
    case GUIDE = 'guide';
    case START = 'start';
    case LEFT_STICK = 'left_stick';
    case RIGHT_STICK = 'right_stick';
    case LEFT_SHOULDER = 'left_shoulder';
    case RIGHT_SHOULDER = 'right_shoulder';
    case DPAD_UP = 'dpad_up';
    case DPAD_DOWN = 'dpad_down';
    case DPAD_LEFT = 'dpad_left';
    case DPAD_RIGHT = 'dpad_right';
}
