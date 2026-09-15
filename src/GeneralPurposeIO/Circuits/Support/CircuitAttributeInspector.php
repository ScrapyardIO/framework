<?php

namespace GeneralPurposeIO\Circuits\Support;

use GeneralPurposeIO\Contracts\Circuits\Attributes\IntegratedCircuit;
use GeneralPurposeIO\Contracts\Circuits\Attributes\Pinout;
use GeneralPurposeIO\Contracts\Circuits\CircuitException;
use ReflectionClass;

final class CircuitAttributeInspector
{
    /**
     * @param  class-string  $class
     * @return list<array{label: string, protocols: list<string>, factory: string, pinout: array<string, string|list<string>>|null, pinout_hints: list<string>}>
     *
     * @throws CircuitException
     */
    public static function protocolOptions(string $class): array
    {
        $reflection = new ReflectionClass($class);
        $icAttrs = $reflection->getAttributes(IntegratedCircuit::class);

        if ($icAttrs === []) {
            throw new CircuitException(
                "Class [{$class}] is missing #[IntegratedCircuit] protocol options."
            );
        }

        /** @var IntegratedCircuit $ic */
        $ic = $icAttrs[0]->newInstance();
        $pinoutAttr = $reflection->getAttributes(Pinout::class);
        /** @var Pinout|null $pinout */
        $pinout = $pinoutAttr === [] ? null : $pinoutAttr[0]->newInstance();

        $options = [];

        foreach ($ic->options() as $index => $option) {
            $options[] = [
                'label' => $option['label'],
                'protocols' => $option['protocols'],
                'factory' => $option['factory'],
                'pinout' => is_null($pinout) ? null : $pinout->forOptionIndex($index),
                'pinout_hints' => is_null($pinout) ? [] : $pinout->hintLines($index),
            ];
        }

        if ($options === []) {
            throw new CircuitException(
                "Class [{$class}] declares no usable #[IntegratedCircuit] protocol options."
            );
        }

        return $options;
    }
}
