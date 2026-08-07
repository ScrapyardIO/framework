<?php

namespace Tests\Sketches\Fixtures;

use Closure;
use Fabricate\Sketches\SketchRunContext;

class RecordingMiddleware
{
    /** @var list<string> */
    public static array $calls = [];

    public function handle(SketchRunContext $context, Closure $next): mixed
    {
        self::$calls[] = 'before:'.$context->name;

        $result = $next($context);

        self::$calls[] = 'after:'.$context->name;

        return $result;
    }
}
