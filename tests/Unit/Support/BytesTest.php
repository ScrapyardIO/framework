<?php

use ScrapyardIO\NutsAndBolts\Bytes;

// array2bytes

test('array2bytes converts an array of byte values to a binary string', function () {
    $result = Bytes::array2bytes([0x00, 0x01]);

    expect($result)
        ->toBeString()
        ->toBe("\x00\x01")
        ->and(strlen($result))
        ->toBe(2);
});

test('array2bytes returns an empty string for an empty array', function () {
    expect(Bytes::array2bytes([]))->toBe('');
});

test('array2bytes handles the full byte range including 0xFF', function () {
    $result = Bytes::array2bytes([0x00, 0xFF, 0x7F]);

    expect($result)
        ->toBe("\x00\xFF\x7F")
        ->and(strlen($result))
        ->toBe(3);
});

test('array2bytes casts numeric strings to ints', function () {
    expect(Bytes::array2bytes(['65', '66']))->toBe('AB');
});

// bytes2array

test('bytes2array converts a binary string to an array of byte values', function () {
    $result = Bytes::bytes2array("\x00\x01");

    expect($result)
        ->toBe([0x00, 0x01])
        ->and(count($result))
        ->toBe(2);
});

test('bytes2array returns an empty array for an empty string', function () {
    expect(Bytes::bytes2array(''))->toBe([]);
});

test('bytes2array handles the full byte range including 0xFF', function () {
    expect(Bytes::bytes2array("\x00\xFF\x7F"))->toBe([0x00, 0xFF, 0x7F]);
});

test('array2bytes and bytes2array round-trip', function () {
    $data = [0x00, 0x01, 0x7F, 0xFF, 0x42];

    expect(Bytes::bytes2array(Bytes::array2bytes($data)))->toBe($data);
});

// byte2bits

test('byte2bits splits a byte into 8 bits keyed from MSB (7) to LSB (0)', function () {
    $result = Bytes::byte2bits(85); // 0b01010101

    expect($result)
        ->toBeArray()
        ->toBe([7 => 0, 6 => 1, 5 => 0, 4 => 1, 3 => 0, 2 => 1, 1 => 0, 0 => 1])
        ->and(count($result))
        ->toBe(8);
});

test('byte2bits handles 0', function () {
    expect(Bytes::byte2bits(0))->toBe([7 => 0, 6 => 0, 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0, 0 => 0]);
});

test('byte2bits handles the maximum byte value 255', function () {
    $result = Bytes::byte2bits(255);

    expect($result)
        ->toBe([7 => 1, 6 => 1, 5 => 1, 4 => 1, 3 => 1, 2 => 1, 1 => 1, 0 => 1])
        ->and(count($result))
        ->toBe(8);
});

test('byte2bits returns an empty array beyond the byte range', function () {
    expect(Bytes::byte2bits(256))->toBe([]);
});

// bits2byte

test('bits2byte packs 8 bits back into a byte value', function () {
    $result = Bytes::bits2byte([1, 0, 1, 0, 1, 0, 1, 0]);

    expect($result)
        ->toBe(85)
        ->and(dechex($result))
        ->toBe('55');
});

test('bits2byte treats missing keys as unset bits', function () {
    expect(Bytes::bits2byte([3 => 1]))->toBe(8);
});

test('bits2byte returns 0 for an empty array', function () {
    expect(Bytes::bits2byte([]))->toBe(0);
});

test('bits2byte treats any truthy value as a set bit', function () {
    expect(Bytes::bits2byte([1, '1', 2, true]))->toBe(0b1111);
});

test('bits2byte ignores indexes beyond bit 7', function () {
    expect(Bytes::bits2byte([0 => 1, 8 => 1, 9 => 1]))->toBe(1);
});

test('byte2bits and bits2byte round-trip for every byte value', function () {
    foreach ([0, 1, 42, 85, 170, 254, 255] as $byte) {
        expect(Bytes::bits2byte(Bytes::byte2bits($byte)))->toBe($byte);
    }
});
