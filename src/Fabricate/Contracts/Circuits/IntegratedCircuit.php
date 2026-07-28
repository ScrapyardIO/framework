<?php

namespace Fabricate\Contracts\Circuits;

interface IntegratedCircuit
{
    public function close(): void;
}