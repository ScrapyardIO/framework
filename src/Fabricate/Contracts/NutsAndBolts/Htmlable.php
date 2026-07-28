<?php

namespace Fabricate\Contracts\NutsAndBolts;

interface Htmlable
{
    /**
     * Get content as a string of HTML.
     *
     * @return string
     */
    public function toHtml(): string;
}
