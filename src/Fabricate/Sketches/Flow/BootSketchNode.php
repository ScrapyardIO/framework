<?php

namespace Fabricate\Sketches\Flow;

use Fabricate\Contracts\Sketches\Sketch;

class BootSketchNode extends Node
{
    public function prep(mixed &$shared): mixed
    {
        return $shared['sketch'] ?? null;
    }

    public function exec(mixed $prepRes): mixed
    {
        if ($prepRes instanceof Sketch) {
            $prepRes->boot();
        }

        return 'default';
    }

    public function post(mixed &$shared, mixed $prepRes, mixed $execRes): mixed
    {
        return $execRes;
    }
}
