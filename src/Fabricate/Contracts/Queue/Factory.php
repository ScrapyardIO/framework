<?php

namespace Fabricate\Contracts\Queue;

interface Factory
{
    public function connection(?string $name = null): Queue;
}
