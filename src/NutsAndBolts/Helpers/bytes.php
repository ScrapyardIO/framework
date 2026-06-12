<?php

if (! function_exists('array2bytes')) {
    function array2bytes(array $data): string
    {
        $bytes = '';
        foreach ($data as $value) {
            $bytes .= chr((int) $value);
        }

        return $bytes;
    }
}

if (! function_exists('bytes2array')) {
    function bytes2array(string $bytes): array
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
}

if (! function_exists('byte2bits')) {
    function byte2bits(int $byte): array
    {
        $results = [];
        if ($byte <= 255) {
            for ($i = 7; $i >= 0; $i--) {
                $results[$i] = ($byte >> $i) & 1;
            }
        } else {
            // @todo - determine bit depths, split and run each byte
        }

        return $results;
    }
}

if (! function_exists('bits2byte')) {
    function bits2byte(array $bits): int
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
