<?php

namespace Fabricate\Core\Events;

class VendorTagPublished
{
    /**
     * The vendor tag that was published.
     *
     * @var string|null
     */
    public $tag;

    /**
     * The publishable paths registered by the tag.
     *
     * @var array
     */
    public $paths;

    /**
     * Create a new event instance.
     *
     * @param  string|null  $tag
     * @param  array  $paths
     */
    public function __construct($tag, $paths)
    {
        $this->tag = $tag;
        $this->paths = $paths;
    }
}
