<?php

namespace BareMetal\Contracts\Actuators\Servos;

interface CircularMotion extends ClosedLoopMotor
{
    /**
     * Spin clockwise at $speed percent (0-100).
     */
    public function clockwise(int $speed = 100): void;

    /**
     * Spin counter-clockwise at $speed percent (0-100).
     */
    public function counterClockwise(int $speed = 100): void;

    public function cw(int $speed = 100): void;

    public function ccw(int $speed = 100): void;

    /**
     * Halt rotation at the deadband / neutral point.
     */
    public function stop(): void;

    /**
     * Set the neutral deadband in degrees.
     */
    public function deadband(int $lower, int $upper): static;
}
