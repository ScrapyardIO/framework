<?php

namespace DeptOfScrapyardRobotics\Tests\Console;

use Fabricate\Console\ConsoleProgram;
use Fabricate\Console\Events\WorkshopStarting;
use Fabricate\Core\Machine;
use Fabricate\Events\Dispatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleProgramTest extends TestCase
{
    public function testItDispatchesItsStartingEvent(): void
    {
        $machine = new Machine(dirname(__DIR__, 4));
        $events = new Dispatcher($machine);
        $started = null;
        $events->listen(WorkshopStarting::class, function (WorkshopStarting $event) use (&$started): void {
            $started = $event;
        });

        $program = new ConsoleProgram($machine, $events, '0.6.0');

        $this->assertInstanceOf(WorkshopStarting::class, $started);
        $this->assertSame($program, $started->workshop);
        $this->assertSame($machine, $program->getScrapyardIO());
    }

    public function testCommandsCanBeCalledAndTheirOutputRetrieved(): void
    {
        $machine = new Machine(dirname(__DIR__, 4));
        $program = new ConsoleProgram($machine, new Dispatcher($machine), '0.6.0');
        $program->addCommand(new EchoMachineCommand());

        $exitCode = $program->call('machine:echo', ['machine' => 'scrapyard']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame("scrapyard\n", $program->output());
    }

    public function testEveryCommandReceivesTheEnvironmentOption(): void
    {
        $machine = new Machine(dirname(__DIR__, 4));
        $program = new ConsoleProgram($machine, new Dispatcher($machine), '0.6.0');

        $this->assertTrue($program->getDefinition()->hasOption('env'));
        $this->assertSame(
            'The environment the command should run under',
            $program->getDefinition()->getOption('env')->getDescription(),
        );
    }

    public function testCommandStringsUseEscapedPhpAndWorkshopBinaries(): void
    {
        $formatted = ConsoleProgram::formatCommandString('machine:echo scrapyard');

        $this->assertStringContainsString('machine:echo scrapyard', $formatted);
        $this->assertStringContainsString('workshop', $formatted);
    }
}

class EchoMachineCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('machine:echo')
            ->addArgument('machine', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln($input->getArgument('machine'));

        return self::SUCCESS;
    }
}
