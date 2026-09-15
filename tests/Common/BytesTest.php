<?php

it('round-trips bytes and arrays', function () {
    expect(bytes2array("\x41\x42\xFF"))->toBe([0x41, 0x42, 0xFF])
        ->and(array2bytes([0x41, 0x42, 0xFF]))->toBe("\x41\x42\xFF");
});

it('splits and joins bits little-index-first', function () {
    expect(byte2bits(0b10100001))->toBe([7 => 1, 6 => 0, 5 => 1, 4 => 0, 3 => 0, 2 => 0, 1 => 0, 0 => 1])
        ->and(bits2byte([0 => 1, 5 => 1, 7 => 1]))->toBe(0b10100001);
});
