<?php

namespace Fabricate\Sketches\Flow;

class ConditionalTransition
{
    public function __construct(
        public BaseNode $src,
        public string $action,
    ) {}

    public function next(BaseNode $target): BaseNode
    {
        return $this->src->next($target, $this->action);
    }
}
