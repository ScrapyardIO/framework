<?php

namespace ScrapyardIO\NutsAndBolts\Enums;

/**
 * The canonical packing scheme a buffer emits.
 *
 * These collapse the panel landscape to three families rather than one entry
 * per driver; bit depth and the remaining shape facts are carried alongside as
 * separate scalars/enums so a single scheme parameterises many panels.
 */
enum PixelFormat: string
{
    case MONO_VERTICAL_PAGE = 'mono_vertical_page';

    case MONO_HORIZONTAL = 'mono_horizontal';

    case ROW_MAJOR = 'row_major';

    case PLANAR = 'planar';
}
