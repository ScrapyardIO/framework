<?php

namespace DeptOfScrapyardRobotics\Tests\Actuation;

use Fabricate\Actuation\Actuator;
use Fabricate\Actuation\ActuatorRegistry;
use Fabricate\Contracts\Actuation\ActuatorException;
use Fabricate\Contracts\Actuation\ActuatorRegistry as ActuatorRegistryContract;
use Fabricate\Contracts\Circuits\IntegratedCircuit;
use PHPUnit\Framework\TestCase;

class ActuatorRegistryTest extends TestCase
{
    public function testItRegistersAndBuildsActuatorsByType(): void
    {
        $registry = new ActuatorRegistry;
        $registry->addActuator('servo', FakeActuator::class);

        $actuator = $registry->type('servo', 'pwm-0');

        $this->assertInstanceOf(ActuatorRegistryContract::class, $registry);
        $this->assertInstanceOf(FakeActuator::class, $actuator);
        $this->assertSame('pwm-0', $actuator->driver());
        $this->assertSame(['servo' => FakeActuator::class], $registry->listActuators());
    }

    public function testItIgnoresClassesThatDoNotImplementTheActuatorContract(): void
    {
        $registry = new ActuatorRegistry;
        $registry->addActuator('invalid', \stdClass::class);

        $this->assertSame([], $registry->listActuators());
    }

    public function testItRejectsUnknownActuatorTypes(): void
    {
        $this->expectException(ActuatorException::class);
        $this->expectExceptionMessage('Actuator [missing] not registered.');

        (new ActuatorRegistry)->type('missing', 'driver');
    }
}

class FakeActuator extends Actuator
{
    public static function circuit(string $driver): static
    {
        return new static(new FakeIntegratedCircuit($driver));
    }

    public function driver(): string
    {
        /** @var FakeIntegratedCircuit $circuit */
        $circuit = $this->circuit;

        return $circuit->driver;
    }
}

class FakeIntegratedCircuit implements IntegratedCircuit
{
    public function __construct(public readonly string $driver) {}

    public function close(): void {}
}
