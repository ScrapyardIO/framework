<?php

namespace ScrapyardIO\NutsAndBolts\DataObjects;

use InvalidArgumentException;

/**
 * The ordered set of colour planes a channel-sorted buffer/panel exchanges.
 *
 * Each {@see ChannelSpec} is one 1bpp plane. A black/white panel has a single
 * channel, BWR has two (black + red), and so on. The palette lives on a
 * {@see FormatSpec} so a buffer and the panel it feeds describe channels the
 * same way and the transcoder can stay a no-op when the specs match.
 */
readonly class ChannelPalette
{
    /**
     * @var list<ChannelSpec>
     */
    public array $channels;

    public function __construct(ChannelSpec ...$channels)
    {
        if (count($channels) < 1) {
            throw new InvalidArgumentException('A ChannelPalette needs at least one channel.');
        }

        $this->channels = array_values($channels);
    }

    public function count(): int
    {
        return count($this->channels);
    }

    /**
     * The logical draw-colour int of every channel, in palette order.
     *
     * @return list<int>
     */
    public function colors(): array
    {
        return array_map(fn (ChannelSpec $channel): int => $channel->color, $this->channels);
    }
}
