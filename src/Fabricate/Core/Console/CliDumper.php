<?php

namespace Fabricate\Core\Console;

use Fabricate\Core\Concerns\ResolvesDumpSource;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper as BaseCliDumper;
use Symfony\Component\VarDumper\VarDumper;

class CliDumper extends BaseCliDumper
{
    use ResolvesDumpSource;

    /**
     * The base path of the application.
     */
    protected string $basePath;

    /**
     * The output instance.
     */
    protected OutputInterface $output;

    /**
     * The compiled view path for the application.
     */
    protected string $compiledViewPath;

    /**
     * If the dumper is currently dumping.
     */
    protected bool $dumping = false;

    /**
     * Create a new CLI dumper instance.
     */
    public function __construct(OutputInterface $output, string $basePath, string $compiledViewPath)
    {
        parent::__construct();

        $this->basePath = $basePath;
        $this->output = $output;
        $this->compiledViewPath = $compiledViewPath;

        $this->setColors($this->supportsColors());
    }

    /**
     * Create a new CLI dumper instance and register it as the default dumper.
     */
    public static function register(string $basePath, string $compiledViewPath): void
    {
        $cloner = new VarCloner;
        $cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

        $dumper = new static(new ConsoleOutput(), $basePath, $compiledViewPath);

        VarDumper::setHandler(static function (mixed $value) use ($dumper, $cloner): void {
            $dumper->dumpWithSource($cloner->cloneVar($value));
        });
    }

    /**
     * Dump a variable with its source file / line.
     */
    public function dumpWithSource(Data $data): void
    {
        if ($this->dumping) {
            $this->dump($data);

            return;
        }

        $this->dumping = true;

        $output = (string) $this->dump($data, true);
        $lines = explode("\n", $output);
        $lastLineKey = array_key_last($lines);

        if (! is_null($lastLineKey) && $lastLineKey > 0) {
            $lines[$lastLineKey - 1] .= $this->getDumpSourceContent();
        }

        $this->output->write(implode("\n", $lines));

        $this->dumping = false;
    }

    /**
     * Get the dump's source console content.
     */
    protected function getDumpSourceContent(): string
    {
        if (is_null($dumpSource = $this->resolveDumpSource())) {
            return '';
        }

        [$file, $relativeFile, $line] = $dumpSource;

        $href = $this->resolveSourceHref($file, $line);

        return sprintf(
            ' <fg=gray>// <fg=gray%s>%s%s</></>',
            is_null($href) ? '' : ";href=$href",
            $relativeFile,
            is_null($line) ? '' : ":$line"
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function supportsColors(): bool
    {
        return $this->output->isDecorated();
    }
}
