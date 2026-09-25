<?php

namespace GeneralPurposeIO\Contracts\IntegratedCircuits;

/**
 * A pixel panel Surface can draw for. transmit() writes packed bytes into
 * panel RAM; without WindowAddressable the origin and size are the whole
 * panel every time. Children: WindowAddressable, RefreshesOnCommand,
 * Switchable.
 *
 * How the bytes are packed is Surface's vocabulary, not GPIO's, so this
 * interface does not name it: a panel driver implements
 * Surface\Contracts\Framebuffers\FormatSpecification alongside this, and
 * Surface asks for both. Keeping it out is what lets a PWM fan or an
 * accelerometer install gpio/contracts without a graphics package.
 */
interface DisplayPanel extends IntegratedCircuit
{
    public function width(): int;

    public function height(): int;

    /**
     * Bytes packed per formatSpec() into the rectangle at (origin_x, origin_y).
     * Null width or height means the whole panel.
     *
     * @param  list<int>  $raw_data
     */
    public function transmit(int $origin_x, int $origin_y, array $raw_data, ?int $frame_width = null, ?int $frame_height = null): void;
}
