<?php

use ScrapyardIO\NutsAndBolts\Bytes;

if (! function_exists('array2bytes')) {
    function array2bytes(array $data): string
    {
        return Bytes::array2bytes($data);
    }
}

if (! function_exists('bytes2array')) {
    function bytes2array(string $bytes): array
    {
        return Bytes::bytes2array($bytes);
    }
}

if (! function_exists('byte2bits')) {
    function byte2bits(int $byte): array
    {
        return Bytes::byte2bits($byte);
    }
}

if (! function_exists('bits2byte')) {
    function bits2byte(array $bits): int
    {
        return Bytes::bits2byte($bits);
    }
}
