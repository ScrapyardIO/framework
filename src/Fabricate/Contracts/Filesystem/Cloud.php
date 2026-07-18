<?php

namespace Fabricate\Contracts\Filesystem;

interface Cloud extends Filesystem
{
    /**
     * Get the URL for the file at the given path.
     */
    public function url($path);
}
