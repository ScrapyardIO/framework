<?php

namespace Fabricate\Sketches\Flow;

use Exception;
use Throwable;

class Node extends BaseNode
{
    protected int $currentRetry = 0;

    public function __construct(
        public int $maxRetries = 1,
        public int $waitSeconds = 0,
    ) {
        parent::__construct();
    }

    public function execFallback(mixed $prepRes, Throwable $e): mixed
    {
        throw $e;
    }

    public function runExec(mixed $prepRes): mixed
    {
        for ($this->currentRetry = 0; $this->currentRetry < $this->maxRetries; $this->currentRetry++) {
            try {
                return parent::runExec($prepRes);
            } catch (Exception $e) {
                if ($this->currentRetry === $this->maxRetries - 1) {
                    return $this->execFallback($prepRes, $e);
                }

                if ($this->waitSeconds > 0) {
                    sleep($this->waitSeconds);
                }
            }
        }

        return null;
    }
}
