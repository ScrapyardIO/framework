<?php

namespace GeneralPurposeIO\Contracts\IntegratedCircuits;

use Surface\Contracts\Framebuffers\FormatSpecification;

/**
 * A pixel panel Surface can draw for. formatSpec() says how transmit() wants
 * its bytes packed; transmit() writes them into panel RAM. Without
 * WindowAddressable the origin and size are the whole panel every time.
 * Children: WindowAddressable, RefreshesOnCommand, Switchable.
 */
interface DisplayPanel extends IntegratedCircuit, FormatSpecification
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
