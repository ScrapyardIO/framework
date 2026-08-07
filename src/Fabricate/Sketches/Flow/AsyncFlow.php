<?php

namespace Fabricate\Sketches\Flow;

/**
 * Flow that awaits AsyncNode steps before action routing.
 */
class AsyncFlow extends Flow
{
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

            if ($current instanceof AsyncNode) {
                $lastAction = $current->runAsync($shared);
            } else {
                $lastAction = $current->run($shared);
            }

            $current = $this->getNextNode($current, is_string($lastAction) ? $lastAction : null);

            if (! is_null($current)) {
                $current = clone $current;
            }
        }

        return $lastAction;
    }
}
