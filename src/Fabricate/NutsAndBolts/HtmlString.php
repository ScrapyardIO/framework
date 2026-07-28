<?php

namespace Fabricate\NutsAndBolts;

use Stringable;
use Fabricate\Contracts\NutsAndBolts\Htmlable;

class HtmlString implements Htmlable, Stringable
{
    /**
     * The HTML string.
     *
     * @var string
     */
    protected string $html;

    /**
     * Create a new HTML string instance.
     *
     * @param string $html
     */
    public function __construct(string $html = '')
    {
        $this->html = $html;
    }

    /**
     * Get the HTML string.
     *
     * @return string
     */
    public function toHtml(): string
    {
        return $this->html;
    }

    /**
     * Determine if the given HTML string is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return ($this->html ?? '') === '';
    }

    /**
     * Determine if the given HTML string is not empty.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Get the HTML string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toHtml() ?? '';
    }
}
