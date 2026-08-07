<?php

namespace Fabricate\Console\Concerns;

use Closure;
use Fabricate\Console\View\Components\Factory;
use Fabricate\NutsAndBolts\Str;
use Fabricate\Console\OutputStyle;
use Fabricate\Console\CommandInput;
use Fabricate\NutsAndBolts\Contracts\Arrayable;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

trait InteractsWithIO
{
    /**
     * The console components factory.
     *
     * @var Factory|null
     */
    protected ?Factory $components = null;

    /**
     * The input interface implementation.
     *
     * @var InputInterface|null
     */
    protected ?InputInterface $input  = null;

    /**
     * The output interface implementation.
     *
     * @var ?OutputStyle
     */
    protected ?OutputStyle $output  = null;

    /**
     * The default verbosity of output commands.
     *
     * @var OutputInterface::VERBOSITY_*
     */
    protected int $verbosity = OutputInterface::VERBOSITY_NORMAL;

    /**
     * The mapping between human-readable verbosity levels and Symfony's OutputInterface.
     *
     * @var array<string, OutputInterface::VERBOSITY_*>
     */
    protected array $verbosityMap = [
        'v' => OutputInterface::VERBOSITY_VERBOSE,
        'vv' => OutputInterface::VERBOSITY_VERY_VERBOSE,
        'vvv' => OutputInterface::VERBOSITY_DEBUG,
        'quiet' => OutputInterface::VERBOSITY_QUIET,
        'normal' => OutputInterface::VERBOSITY_NORMAL,
    ];

    /**
     * Retrieve the command's input as a CommandInput instance or retrieve an input item.
     *
     * @param string|null $key
     * @param mixed|null $default
     * @return ($key is null ? CommandInput : mixed)
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        $input = new CommandInput($this->arguments(), $this->options());

        return is_null($key) ? $input : data_get($input->all(), $key, $default);
    }

    /**
     * Determine if the given argument is present.
     *
     * @param int|string $name
     * @return bool
     */
    public function hasArgument(int|string $name): bool
    {
        return $this->input->hasArgument($name);
    }

    /**
     * Get the value of a command argument.
     *
     * @param string|null $key
     * @return ($key is null ? array<array|string|float|int|bool|null> : array|string|float|int|bool|null)
     */
    public function argument(?string $key = null): array|float|bool|int|string|null
    {
        if (is_null($key)) {
            return $this->input->getArguments();
        }

        return $this->input->getArgument($key);
    }

    /**
     * Get every argument passed to the command.
     *
     * @return array<array|string|float|int|bool|null>
     */
    public function arguments(): array
    {
        return $this->argument();
    }

    /**
     * Determine whether the option is defined in the command signature.
     *
     * @param string $name
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return $this->input->hasOption($name);
    }

    /**
     * Get the value of a command option.
     *
     * @param string|null $key
     * @return ($key is null ? array<array|string|float|int|bool|null> : array|string|float|int|bool|null)
     */
    public function option(?string $key = null): array|float|bool|int|string|null
    {
        if (is_null($key)) {
            return $this->input->getOptions();
        }

        return $this->input->getOption($key);
    }

    /**
     * Get every option passed to the command.
     *
     * @return array<array|string|float|int|bool|null>
     */
    public function options(): array
    {
        return $this->option();
    }

    /**
     * Confirm a question with the user.
     *
     * @param string $question
     * @param bool $default
     * @return bool
     */
    public function confirm(string $question, bool $default = false): bool
    {
        return $this->output->confirm($question, $default);
    }

    /**
     * Prompt the user for input.
     *
     * @param string $question
     * @param string|null $default
     * @return mixed
     */
    public function ask(string $question, ?string $default = null): mixed
    {
        return $this->output->ask($question, $default);
    }

    /**
     * Prompt the user for input with autocompletion.
     *
     * @param string $question
     * @param callable|array $choices
     * @param string|null $default
     * @return mixed
     */
    public function anticipate(string $question, callable|array $choices, ?string $default = null): mixed
    {
        return $this->askWithCompletion($question, $choices, $default);
    }

    /**
     * Prompt the user for input with autocompletion.
     *
     * @param string $question
     * @param iterable|(callable(string): string[]) $choices
     * @param string|null $default
     * @return mixed
     */
    public function askWithCompletion(string $question, iterable $choices, ?string $default = null): mixed
    {
        $question = new Question($question, $default);

        is_callable($choices)
            ? $question->setAutocompleterCallback($choices)
            : $question->setAutocompleterValues($choices);

        return $this->output->askQuestion($question);
    }

    /**
     * Prompt the user for input but hide the answer from the console.
     *
     * @param string $question
     * @param bool $fallback
     * @return mixed
     */
    public function secret(string $question, bool $fallback = true): mixed
    {
        $question = new Question($question);

        $question->setHidden(true)->setHiddenFallback($fallback);

        return $this->output->askQuestion($question);
    }

    /**
     * Give the user a single choice from an array of answers.
     *
     * @param string $question
     * @param  array<\Stringable|string|float|int|bool>  $choices
     * @param int|string|null $default
     * @param  ?positive-int $attempts
     * @param bool $multiple
     * @return string|array
     */
    public function choice(string $question, array $choices, int|string|null $default = null, ?int $attempts = null, bool $multiple = false): array|string
    {
        $question = new ChoiceQuestion($question, $choices, $default);

        $question->setMaxAttempts($attempts)->setMultiselect($multiple);

        return $this->output->askQuestion($question);
    }

    /**
     * Format input to textual table.
     *
     * @param array $headers
     * @param array|Arrayable $rows
     * @param string|TableStyle $tableStyle
     * @param  array<int, TableStyle|string>  $columnStyles
     * @return void
     */
    public function table(array $headers, Arrayable|array $rows, TableStyle|string $tableStyle = 'default', array $columnStyles = []): void
    {
        $table = new Table($this->output);

        if ($rows instanceof Arrayable) {
            $rows = $rows->toArray();
        }

        $table->setHeaders((array) $headers)->setRows($rows)->setStyle($tableStyle);

        foreach ($columnStyles as $columnIndex => $columnStyle) {
            $table->setColumnStyle($columnIndex, $columnStyle);
        }

        $table->render();
    }

    /**
     * Execute a given callback while advancing a progress bar.
     *
     * @template TKey of array-key
     * @template TValue
     * @template TIterable of iterable<TKey, TValue>
     *
     * @param int|TIterable $totalSteps
     * @param  Closure(ProgressBar): mixed|Closure(TValue, ProgressBar, TKey): mixed  $callback
     * @return ($totalSteps is iterable ? TIterable : void)
     */
    public function withProgressBar(array|int $totalSteps, Closure $callback)
    {
        $bar = $this->output->createProgressBar(
            is_iterable($totalSteps) ? count($totalSteps) : $totalSteps
        );

        $bar->start();

        if (is_iterable($totalSteps)) {
            foreach ($totalSteps as $key => $value) {
                $callback($value, $bar, $key);

                $bar->advance();
            }
        } else {
            $callback($bar);
        }

        $bar->finish();

        if (is_iterable($totalSteps)) {
            return $totalSteps;
        }
    }

    /**
     * Write a string as information output.
     *
     * @param string $string
     * @param 'v'|'vv'|'vvv'|'quiet'|'normal'|null $verbosity
     * @return void
     */
    public function info(string $string, ?string $verbosity = null): void
    {
        $this->line($string, 'info', $verbosity);
    }

    /**
     * Write a string as standard output.
     *
     * @param string $string
     * @param 'info'|'comment'|'question'|'error'|'warn'|'alert'|null $style
     * @param 'v'|'vv'|'vvv'|'quiet'|'normal'|null $verbosity
     * @return void
     */
    public function line(string $string, ?string $style = null, ?string $verbosity = null): void
    {
        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->output->writeln($styled, $this->parseVerbosity($verbosity));
    }

    /**
     * Write a string as comment output.
     *
     * @param string $string
     * @param 'v'|'vv'|'vvv'|'quiet'|'normal'|null $verbosity
     * @return void
     */
    public function comment(string $string, ?string $verbosity = null): void
    {
        $this->line($string, 'comment', $verbosity);
    }

    /**
     * Write a string as question output.
     *
     * @param string $string
     * @param 'v'|'vv'|'vvv'|'quiet'|'normal'|null $verbosity
     * @return void
     */
    public function question(string $string, ?string $verbosity = null): void
    {
        $this->line($string, 'question', $verbosity);
    }

    /**
     * Write a string as error output.
     *
     * @param string $string
     * @param 'v'|'vv'|'vvv'|'quiet'|'normal'|null $verbosity
     * @return void
     */
    public function error(string $string, ?string $verbosity = null): void
    {
        $this->line($string, 'error', $verbosity);
    }

    /**
     * Write a string as warning output.
     *
     * @param string $string
     * @param 'v'|'vv'|'vvv'|'quiet'|'normal'|null $verbosity
     * @return void
     */
    public function warn(string $string, ?string $verbosity = null): void
    {
        if (! $this->output->getFormatter()->hasStyle('warning')) {
            $style = new OutputFormatterStyle('yellow');

            $this->output->getFormatter()->setStyle('warning', $style);
        }

        $this->line($string, 'warning', $verbosity);
    }

    /**
     * Write a string in an alert box.
     *
     * @param string $string
     * @param 'v'|'vv'|'vvv'|'quiet'|'normal'|null $verbosity
     * @return void
     */
    public function alert(string $string, ?string $verbosity = null): void
    {
        $length = Str::length(strip_tags($string)) + 12;

        $this->comment(str_repeat('*', $length), $verbosity);
        $this->comment('*     '.$string.'     *', $verbosity);
        $this->comment(str_repeat('*', $length), $verbosity);

        $this->comment('', $verbosity);
    }

    /**
     * Write a blank line.
     *
     * @param int $count
     * @return $this
     */
    public function newLine(int $count = 1): static
    {
        $this->output->newLine($count);

        return $this;
    }

    /**
     * Set the input interface implementation.
     *
     * @param InputInterface $input
     * @return void
     */
    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }

    /**
     * Set the output interface implementation.
     *
     * @param OutputStyle $output
     * @return void
     */
    public function setOutput(OutputStyle $output): void
    {
        $this->output = $output;
    }

    /**
     * Set the verbosity level.
     *
     * @param 'v'|'vv'|'vvv'|'quiet'|'normal' $level
     * @return void
     */
    protected function setVerbosity(string $level): void
    {
        $this->verbosity = $this->parseVerbosity($level);
    }

    /**
     * Get the verbosity level in terms of Symfony's OutputInterface level.
     *
     * @param 'v'|'vv'|'vvv'|'quiet'|'normal'|null $level
     * @return int
     */
    protected function parseVerbosity(?string $level = null): int
    {
        $level ??= '';

        if (isset($this->verbosityMap[$level])) {
            $level = $this->verbosityMap[$level];
        } elseif (! is_int($level)) {
            $level = $this->verbosity;
        }

        return $level;
    }

    /**
     * Get the output implementation.
     *
     * @return OutputStyle
     */
    public function getOutput(): OutputStyle
    {
        return $this->output;
    }
}