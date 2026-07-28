<?php

namespace Fabricate\Contracts\Core;

use Throwable;

interface ExceptionRenderer
{
    /**
     * Renders the given exception as HTML.
     *
     * @param Throwable $throwable
     * @return string
     */
    public function render(Throwable $throwable): string;
}