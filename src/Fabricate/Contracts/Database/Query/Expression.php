<?php

namespace Fabricate\Contracts\Database\Query;

use Fabricate\Database\Grammar;

interface Expression
{
    /**
     * Get the value of the expression.
     *
     * @param  \Fabricate\Database\Grammar  $grammar
     * @return string|int|float
     */
    public function getValue(Grammar $grammar);
}
