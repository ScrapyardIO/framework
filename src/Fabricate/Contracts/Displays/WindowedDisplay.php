<?php

namespace Fabricate\Contracts\Displays;

interface WindowedDisplay extends Display
{
    /**
     * True when the user (or OS) has asked the window to close.
     *
     * Drivers must poll native events as part of answering this so chrome
     * close buttons are observed even between frames.
     */
    public function shouldClose(): bool;
}