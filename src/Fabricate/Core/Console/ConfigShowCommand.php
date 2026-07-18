<?php

namespace Fabricate\Core\Console;

use Fabricate\Console\Command;
use Fabricate\NutsAndBolts\Arr;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'config:show')]
class ConfigShowCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string|null
     */
    protected ?string $signature = 'config:show {config : The configuration file or key to show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Display all of the values for a given configuration file or key';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $config = $this->argument('config');

        if (! config()->has($config)) {
            $this->fail("Configuration file or key <comment>{$config}</comment> does not exist.");
        }

        $this->newLine();
        $this->render($config);
        $this->newLine();

        return static::SUCCESS;
    }

    /**
     * Render the configuration values.
     *
     * @param  string  $name
     * @return void
     */
    public function render(string $name): void
    {
        $data = config($name);

        if (! is_array($data)) {
            $this->title($name, $this->formatValue($data));

            return;
        }

        $this->title($name);

        foreach (Arr::dot($data) as $key => $value) {
            $this->components->twoColumnDetail(
                $this->formatKey($key),
                $this->formatValue($value)
            );
        }
    }

    /**
     * Render the title.
     *
     * @param  string  $title
     * @param  string|null  $subtitle
     * @return void
     */
    public function title(string $title, ?string $subtitle = null): void
    {
        $this->components->twoColumnDetail(
            "<fg=green;options=bold>{$title}</>",
            $subtitle,
        );
    }

    /**
     * Format the given configuration key.
     *
     * @param  string  $key
     * @return string
     */
    protected function formatKey(string $key): string
    {
        return preg_replace_callback(
            '/(.*)\.(.*)$/', fn ($matches) => sprintf(
                '<fg=gray>%s ⇁</> %s',
                str_replace('.', ' ⇁ ', $matches[1]),
                $matches[2]
            ), $key
        );
    }

    /**
     * Format the given configuration value.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function formatValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => sprintf('<fg=#ef8414;options=bold>%s</>', $value ? 'true' : 'false'),
            is_null($value) => '<fg=#ef8414;options=bold>null</>',
            is_numeric($value) => "<fg=#ef8414;options=bold>{$value}</>",
            is_array($value) => '[]',
            is_object($value) => get_class($value),
            is_string($value) => $value,
            default => print_r($value, true),
        };
    }
}
