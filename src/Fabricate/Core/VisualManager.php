<?php

namespace Fabricate\Core;

use Fabricate\Contracts\Core\VisualException;
use Fabricate\Contracts\Core\VisualPresentation;
use Fabricate\Displays\DisplayRegistry;
use Fabricate\Framebuffers\FramebufferManager;
use Fabricate\Rendering\RenderManager;

class VisualManager
{
    public function __construct(
        protected DisplayRegistry $displays,
        protected RenderManager $renderers,
        protected FramebufferManager $framebuffers,
    ) {}

    public function display(string $type, string ...$arguments): PendingVisualPresentation
    {
        $type = strtolower($type);

        $display = match ($type) {
            'embedded' => count($arguments) === 2
                ? $this->displays->embedded($arguments[0], $arguments[1])
                : throw VisualException::invalidDisplayArguments($type),
            'window', 'windowed' => count($arguments) === 1
                ? $this->displays->window($arguments[0])
                : throw VisualException::invalidDisplayArguments($type),
            default => throw VisualException::unsupportedDisplayType($type),
        };

        return new PendingVisualPresentation(
            $display,
            $this->renderers,
            $this->framebuffers,
        );
    }

    /**
     * Build a presentation from config('displays.main').
     *
     * Console mains have no physical display — returns null so callers can
     * fall back to CLI output.
     */
    public function main(): ?VisualPresentation
    {
        $main = config('displays.main');

        if (! is_array($main)) {
            throw VisualException::invalidMainDisplay('expected an array.');
        }

        $type = strtolower((string) ($main['type'] ?? ''));

        if ($type === '') {
            throw VisualException::invalidMainDisplay('missing type.');
        }

        if ($type === 'console') {
            return null;
        }

        $renderer = $main['renderer'] ?? null;
        $buffer = $main['buffer'] ?? null;

        if (! is_string($renderer) || $renderer === '') {
            throw VisualException::invalidMainDisplay('missing renderer.');
        }

        if (! is_string($buffer) || $buffer === '') {
            throw VisualException::invalidMainDisplay('missing buffer.');
        }

        $pending = match ($type) {
            'window', 'windowed' => $this->pendingWindowed($main),
            'embedded' => $this->pendingEmbedded($main),
            default => throw VisualException::unsupportedDisplayType($type),
        };

        return $pending
            ->renderer($renderer)
            ->buffer($buffer)
            ->present();
    }

    /**
     * @param  array<string, mixed>  $main
     */
    protected function pendingWindowed(array $main): PendingVisualPresentation
    {
        $driver = $main['driver'] ?? null;

        if (! is_string($driver) || $driver === '') {
            throw VisualException::invalidMainDisplay('windowed mains require driver.');
        }

        return $this->display('windowed', $driver);
    }

    /**
     * @param  array<string, mixed>  $main
     */
    protected function pendingEmbedded(array $main): PendingVisualPresentation
    {
        $driver = $main['driver'] ?? null;
        $circuit = $main['circuit'] ?? null;

        if (! is_string($driver) || $driver === '') {
            throw VisualException::invalidMainDisplay('embedded mains require driver.');
        }

        if (! is_string($circuit) || $circuit === '') {
            throw VisualException::invalidMainDisplay('embedded mains require circuit.');
        }

        return $this->display('embedded', $driver, $circuit);
    }
}
