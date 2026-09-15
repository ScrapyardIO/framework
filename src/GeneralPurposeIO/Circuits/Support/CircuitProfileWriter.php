<?php

namespace GeneralPurposeIO\Circuits\Support;

use GeneralPurposeIO\Contracts\Circuits\CircuitException;

final class CircuitProfileWriter
{
    /**
     * @param  array<string, mixed>  $params
     *
     * @throws CircuitException
     */
    public static function append(
        string $configPath,
        string $profileName,
        string $ic,
        string $protocol,
        array $params = ['boot_now' => true],
    ): void {
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $profileName)) {
            throw new CircuitException(
                "Profile name [{$profileName}] must be a simple identifier (letters, numbers, _ or -)."
            );
        }

        if (! is_file($configPath)) {
            throw new CircuitException(
                "Circuits config not found at [{$configPath}]. Publish it first: workshop vendor:publish --tag=gpio-circuits-config"
            );
        }

        $contents = file_get_contents($configPath);

        if ($contents === false) {
            throw new CircuitException("Unable to read circuits config at [{$configPath}].");
        }

        if (preg_match('/[\'"]'.preg_quote($profileName, '/').'[\'"]\s*=>/', $contents) === 1) {
            throw new CircuitException("Circuit profile [{$profileName}] already exists in [{$configPath}].");
        }

        if (! array_key_exists('boot_now', $params)) {
            $params = ['boot_now' => true] + $params;
        }

        $block = self::renderBlock($profileName, $ic, $protocol, $params);
        $updated = self::insertBeforeClosing($contents, $block);

        if (file_put_contents($configPath, $updated) === false) {
            throw new CircuitException("Unable to write circuits config at [{$configPath}].");
        }
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected static function renderBlock(
        string $profileName,
        string $ic,
        string $protocol,
        array $params,
    ): string {
        $lines = [
            "    '{$profileName}' => [",
            "        'ic' => '{$ic}',",
            "        'protocol' => '{$protocol}',",
            "        'params' => [",
        ];

        foreach ($params as $key => $value) {
            $lines[] = '            '.var_export((string) $key, true).' => '.self::exportValue($value).',';
        }

        $lines[] = '        ],';
        $lines[] = '    ],';
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }

    protected static function exportValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return var_export($value, true);
        }

        return var_export($value, true);
    }

    protected static function insertBeforeClosing(string $contents, string $block): string
    {
        $pos = strrpos($contents, '];');

        if ($pos === false) {
            throw new CircuitException('Circuits config does not look like a PHP array return (missing closing ];).');
        }

        return substr($contents, 0, $pos).$block.substr($contents, $pos);
    }
}
