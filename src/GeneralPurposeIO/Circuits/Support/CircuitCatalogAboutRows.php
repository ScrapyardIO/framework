<?php

namespace GeneralPurposeIO\Circuits\Support;

use Voyager\System\Console\AboutCommand;
use GeneralPurposeIO\Circuits\CircuitRegistry;
use GeneralPurposeIO\Contracts\Circuits\CircuitException;
use Throwable;

/**
 * Build Workshop `about` rows for catalog-registered ICs (not profiles).
 *
 * Left: catalog slug. Right: #[IntegratedCircuit] option labels
 * (e.g. SPI+DigitalIO / SPI / DigitalIO).
 */
final class CircuitCatalogAboutRows
{
    /**
     * @return array<string, mixed>
     */
    public static function for(CircuitRegistry $registry): array
    {
        $catalog = $registry->listCircuits();

        if ($catalog === []) {
            return [];
        }

        ksort($catalog, SORT_STRING);

        $rows = [];

        foreach ($catalog as $slug => $class) {
            $rows[$slug] = self::formatOptions((string) $class);
        }

        return $rows;
    }

    /**
     * @param  class-string  $class
     */
    protected static function formatOptions(string $class): mixed
    {
        try {
            $labels = array_values(array_map(
                static fn (array $option): string => $option['label'],
                CircuitAttributeInspector::protocolOptions($class),
            ));
        } catch (CircuitException|Throwable) {
            return AboutCommand::format(
                value: [],
                console: static fn () => '<fg=yellow;options=bold>?</>',
                json: static fn () => [],
            );
        }

        if ($labels === []) {
            return AboutCommand::format(
                value: [],
                console: static fn () => '<fg=yellow;options=bold>?</>',
                json: static fn () => [],
            );
        }

        return AboutCommand::format(
            value: $labels,
            console: static function (array $labels): string {
                $styled = array_map(
                    static fn (string $label): string => '<fg=cyan;options=bold>'.$label.'</>',
                    $labels,
                );

                return implode(' <fg=gray;options=bold>/</> ', $styled);
            },
            json: static fn (array $labels): array => $labels,
        );
    }
}
