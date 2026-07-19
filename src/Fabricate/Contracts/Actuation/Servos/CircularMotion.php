<?php

namespace Fabricate\Contracts\Actuation\Servos;

interface CircularMotion extends ClosedLoopMotor
{
    public function clockwise(int $speed = 100): void;

    public function counterClockwise(int $speed = 100): void;

    public function cw(int $speed = 100): void;

    public function ccw(int $speed = 100): void;

    public function stop(): void;

    public function deadband(int $lower, int $upper): static;
}
