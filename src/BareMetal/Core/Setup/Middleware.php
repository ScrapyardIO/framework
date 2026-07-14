<?php

namespace BareMetal\Core\Setup;

class Middleware
{
    /**
     * The user defined global middleware stack.
     */
    protected array $global = [];

    /**
     * The middleware that should be prepended to the global middleware stack.
     */
    protected array $prepends = [];

    /**
     * The middleware that should be appended to the global middleware stack.
     */
    protected array $appends = [];

    /**
     * The middleware that should be removed from the global middleware stack.
     */
    protected array $removals = [];

    /**
     * The middleware that should be replaced in the global middleware stack.
     */
    protected array $replacements = [];

    /**
     * The user defined middleware groups.
     */
    protected array $groups = [];

    /**
     * The middleware that should be prepended to the specified groups.
     */
    protected array $group_prepends = [];

    /**
     * The middleware that should be appended to the specified groups.
     */
    protected array $group_appends = [];

    /**
     * The middleware that should be removed from the specified groups.
     */
    protected array $group_removals = [];

    /**
     * The middleware that should be replaced in the specified groups.
     */
    protected array $group_replacements = [];

    /**
     * Indicates if Redis throttling should be applied.
     */
    protected bool $throttleWithRedis = false;

    /**
     * The custom middleware aliases.
     */
    protected array $custom_aliases = [];

    /**
     * The custom middleware priority definition.
     */
    protected array $priority = [];

    /**
     * The middleware to prepend to the middleware priority definition.
     */
    protected array $prepend_priority = [];

    /**
     * The middleware to append to the middleware priority definition.
     */
    protected array $append_priority = [];
}
