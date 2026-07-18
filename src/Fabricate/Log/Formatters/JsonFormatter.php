<?php

namespace Fabricate\Log\Formatters;

use Fabricate\Chassis\Chassis;
use Fabricate\Contracts\Debug\ExceptionHandler;
use Monolog\Formatter\JsonFormatter as MonologJsonFormatter;
use Throwable;

class JsonFormatter extends MonologJsonFormatter
{
    #[\Override]
    protected function normalizeException(Throwable $e, int $depth = 0): array
    {
        $response = parent::normalizeException($e, $depth);

        try {
            $handler = Chassis::getInstance()->make(ExceptionHandler::class);
        } catch (Throwable) {
            return array_merge($this->getExceptionContext($e, $depth), $response);
        }

        if ((! method_exists($handler, 'isReporting')) || ! $handler->isReporting($e)) {
            if (method_exists($handler, 'buildContextForException')
                && is_array($normalizedHandlerExceptionContext = $this->normalize($handler->buildContextForException($e), $depth + 1))
            ) {
                $response = array_merge(
                    $normalizedHandlerExceptionContext,
                    $response
                );
            } elseif (method_exists($e, 'context')) {
                $response = array_merge($this->getExceptionContext($e, $depth), $response);
            }
        }

        return $response;
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getExceptionContext(Throwable $e, int $depth): array
    {
        if (! method_exists($e, 'context')) {
            return [];
        }

        try {
            $exceptionContext = $this->normalize($e->context(), $depth + 1);
        } catch (Throwable) {
            return [];
        }

        return is_array($exceptionContext) ? $exceptionContext : [];
    }
}
