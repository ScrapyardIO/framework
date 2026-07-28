<?php

namespace DeptOfScrapyardRobotics\Tests\Console;

use Fabricate\Console\Parser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testItParsesACommandWithoutParameters(): void
    {
        $this->assertSame(['machine:start', [], []], Parser::parse('machine:start'));
    }

    public function testItParsesRequiredOptionalDefaultAndArrayArguments(): void
    {
        [$name, $arguments] = Parser::parse(
            'machine:start
            {machine : Machine name}
            {environment=testing : Runtime environment}
            {targets?* : Optional targets}
            {steps=*first,second : Default steps}',
        );

        $this->assertSame('machine:start', $name);
        $this->assertCount(4, $arguments);
        $this->assertTrue($arguments[0]->isRequired());
        $this->assertSame('Machine name', $arguments[0]->getDescription());
        $this->assertSame('testing', $arguments[1]->getDefault());
        $this->assertTrue($arguments[2]->isArray());
        $this->assertFalse($arguments[2]->isRequired());
        $this->assertSame(['first', 'second'], $arguments[3]->getDefault());
    }

    public function testItParsesFlagsValueOptionsArraysShortcutsAndDescriptions(): void
    {
        [, , $options] = Parser::parse(
            'machine:start
            {--force : Force startup}
            {--E|environment=testing : Runtime environment}
            {--target=* : Optional targets}
            {--steps=*first,second : Default steps}',
        );

        $this->assertCount(4, $options);
        $this->assertFalse($options[0]->acceptValue());
        $this->assertSame('Force startup', $options[0]->getDescription());
        $this->assertSame('E', $options[1]->getShortcut());
        $this->assertSame('testing', $options[1]->getDefault());
        $this->assertTrue($options[2]->isValueOptional());
        $this->assertTrue($options[2]->isArray());
        $this->assertSame([], $options[2]->getDefault());
        $this->assertSame(['first', 'second'], $options[3]->getDefault());
    }

    public function testAnEmptySignatureIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to determine command name from signature.');

        Parser::parse('   ');
    }
}
