<?php

use GeneralPurposeIO\Contracts\IntegratedCircuits\DisplayPanel;
use GeneralPurposeIO\Contracts\IntegratedCircuits\IntegratedCircuit;
use GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshesOnCommand;
use GeneralPurposeIO\Contracts\IntegratedCircuits\RefreshMode;
use GeneralPurposeIO\Contracts\IntegratedCircuits\Switchable;
use GeneralPurposeIO\Contracts\IntegratedCircuits\WindowAddressable;
use Surface\Contracts\Framebuffers\FormatSpecification;

/** @return list<array{string, string, bool}> name, type, has default */
function displayPanelParams(string $interface, string $method): array
{
    return array_map(
        fn (ReflectionParameter $p): array => [$p->getName(), (string) $p->getType(), $p->isDefaultValueAvailable()],
        (new ReflectionMethod($interface, $method))->getParameters(),
    );
}

/** @return list<string> methods the interface declares itself, not inherited */
function ownMethods(string $interface): array
{
    return array_values(array_map(
        fn (ReflectionMethod $m): string => $m->getName(),
        array_filter((new ReflectionClass($interface))->getMethods(), fn (ReflectionMethod $m): bool => $m->getDeclaringClass()->getName() === $interface),
    ));
}

it('roots DisplayPanel in IntegratedCircuit and FormatSpecification', function (): void {
    expect(is_subclass_of(DisplayPanel::class, IntegratedCircuit::class))->toBeTrue()
        ->and(is_subclass_of(DisplayPanel::class, FormatSpecification::class))->toBeTrue();
});

it('gives DisplayPanel the transmit signature the shipped chips already have', function (): void {
    expect((string) (new ReflectionMethod(DisplayPanel::class, 'transmit'))->getReturnType())->toBe('void')
        ->and(displayPanelParams(DisplayPanel::class, 'transmit'))->toBe([
            ['origin_x', 'int', false],
            ['origin_y', 'int', false],
            ['raw_data', 'array', false],
            ['frame_width', '?int', true],
            ['frame_height', '?int', true],
        ])
        ->and(ownMethods(DisplayPanel::class))->toBe(['width', 'height', 'transmit']);
});

it('marks window-addressable panels with an empty child interface', function (): void {
    expect(is_subclass_of(WindowAddressable::class, DisplayPanel::class))->toBeTrue()
        ->and(ownMethods(WindowAddressable::class))->toBe([]);
});

it('lets a panel refresh on command, full by default', function (): void {
    expect(is_subclass_of(RefreshesOnCommand::class, DisplayPanel::class))->toBeTrue()
        ->and(ownMethods(RefreshesOnCommand::class))->toBe(['refresh'])
        ->and(displayPanelParams(RefreshesOnCommand::class, 'refresh'))->toBe([['mode', RefreshMode::class, true]])
        ->and((new ReflectionMethod(RefreshesOnCommand::class, 'refresh'))->getParameters()[0]->getDefaultValue())->toBe(RefreshMode::FULL)
        ->and((string) (new ReflectionMethod(RefreshesOnCommand::class, 'refresh'))->getReturnType())->toBe('void');
});

it('names two refresh modes, string-backed', function (): void {
    expect(RefreshMode::cases())->toBe([RefreshMode::FULL, RefreshMode::PARTIAL])
        ->and(RefreshMode::FULL->value)->toBe('full')
        ->and(RefreshMode::PARTIAL->value)->toBe('partial');
});

it('lets a panel be switched on and off', function (): void {
    expect(is_subclass_of(Switchable::class, DisplayPanel::class))->toBeTrue()
        ->and(ownMethods(Switchable::class))->toBe(['setDisplay'])
        ->and(displayPanelParams(Switchable::class, 'setDisplay'))->toBe([['on', 'bool', false]])
        ->and((string) (new ReflectionMethod(Switchable::class, 'setDisplay'))->getReturnType())->toBe('void');
});
