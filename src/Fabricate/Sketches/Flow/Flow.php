<?php

namespace Fabricate\Sketches\Flow;

class Flow extends BaseNode
{
    protected ?BaseNode $startNode = null;

    public function __construct(?BaseNode $start = null)
    {
        parent::__construct();

        $this->startNode = $start;
    }

    public function start(BaseNode $start): BaseNode
    {
        $this->startNode = $start;

        return $start;
    }

    public function getNextNode(BaseNode $current, ?string $action): ?BaseNode
    {
        return $current->successors[$action ?? 'default'] ?? null;
    }

    /**
     * @param  array<string, mixed>|null  $params
     */
    protected function orchestrate(mixed &$shared, ?array $params = null): mixed
    {
        if (is_null($this->startNode)) {
            return null;
        }

        $current = clone $this->startNode;
        $nodeParams = $params ?? $this->params;
        $lastAction = null;

        while (! is_null($current)) {
            $current->setParams($nodeParams);
            $lastAction = $current->run($shared);
            $current = $this->getNextNode($current, is_string($lastAction) ? $lastAction : null);

            if (! is_null($current)) {
                $current = clone $current;
            }
        }

        return $lastAction;
    }

    public function run(mixed &$shared): mixed
    {
        $prepRes = $this->prep($shared);
        $execRes = $this->orchestrate($shared);

        return $this->post($shared, $prepRes, $execRes);
    }

    public function post(mixed &$shared, mixed $prepRes, mixed $execRes): mixed
    {
        return $execRes;
    }
}
