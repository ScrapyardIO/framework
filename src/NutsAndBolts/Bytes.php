<?php

namespace ScrapyardIO\NutsAndBolts;

final class Bytes
{
    public static function array2bytes(array $data): string
    {
        $bytes = '';
        foreach ($data as $value) {
            $bytes .= chr((int) $value);
        }

        return $bytes;
    }

    public static function bytes2array(string $bytes): array
    {
        $results = [];

        $len = strlen($bytes);
        $i = 0;

        while ($i < $len) {
            $results[] = ord($bytes[$i]);
            $i++;
        }

        return $results;
    }

    public static function byte2bits(int $byte): array
    {
        $results = [];
        if ($byte <= 255) {
            for ($i = 7; $i >= 0; $i--) {
                $results[$i] = ($byte >> $i) & 1;
            }
        }

        return $results;
    }

    public static function bits2byte(array $bits): int
    {
        $byte = 0;
        for ($i = 0; $i <= 7; $i++) {
            if (! empty($bits[$i])) {
                $byte |= (1 << $i);
            }
        }

        return $byte;
    }
}
