<?php

namespace BareMetal\Contracts\Actuators\HumanInput;

/**
 * Raw digital button IC surface — instantaneous down/up only.
 * Edge / hold semantics live on BareMetal\Actuation\HumanInput\BasicButton.
 */
interface BasicButtonFunctionality
{
    public function isDown(): bool;
}
