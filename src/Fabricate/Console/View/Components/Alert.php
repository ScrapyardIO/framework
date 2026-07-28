<?php

namespace Fabricate\Console\View\Components;

use Symfony\Component\Console\Output\OutputInterface;

class Alert extends Component
{
    /**
     * Renders the component using the given arguments.
     *
     * @param string $string
     * @param int $verbosity
     * @return void
     */
    public function render(string $string, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $string = $this->mutate($string, [
            Mutators\EnsureDynamicContentIsHighlighted::class,
            Mutators\EnsurePunctuation::class,
            Mutators\EnsureRelativePaths::class,
        ]);

        $this->renderView('alert', [
            'content' => $string,
        ], $verbosity);
    }
}
