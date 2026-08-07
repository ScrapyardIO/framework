<?php

namespace Fabricate\Sketches\Flow;

/**
 * Decision-based Logic Orchestration graph node.
 */
abstract class BaseNode
{
    /**
     * @var array<string, mixed>
     */
    protected array $params = [];

    /**
     * @var array<string, BaseNode>
     */
    protected array $successors = [];

    public function __construct()
    {
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function next(BaseNode $node, string $action = 'default'): BaseNode
    {
        $this->successors[$action] = $node;

        return $node;
    }

    public function prep(mixed &$shared): mixed
    {
        return null;
    }

    public function exec(mixed $prepRes): mixed
    {
        return null;
    }

    public function post(mixed &$shared, mixed $prepRes, mixed $execRes): mixed
    {
        return null;
    }

    public function runExec(mixed $prepRes): mixed
    {
        return $this->exec($prepRes);
    }

    public function run(mixed &$shared): mixed
    {
        $prepRes = $this->prep($shared);
        $execRes = $this->runExec($prepRes);

        return $this->post($shared, $prepRes, $execRes);
    }

    public function on(string $action): ConditionalTransition
    {
        return new ConditionalTransition($this, $action);
    }
}
