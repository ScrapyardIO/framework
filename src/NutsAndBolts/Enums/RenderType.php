<?php

namespace ScrapyardIO\NutsAndBolts\Enums;

/**
 * Whether a dumped buffer represents the whole surface or a partial update.
 */
enum RenderType: string
{
    case FULL = 'full';

    case PARTIAL = 'partial';
}
