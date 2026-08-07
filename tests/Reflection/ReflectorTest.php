<?php

use Fabricate\NutsAndBolts\Reflector;

test('reflector isCallable accepts closures and rejects broken arrays', function () {
    expect(Reflector::isCallable(fn () => true))->toBeTrue()
        ->and(Reflector::isCallable('strlen'))->toBeTrue()
        ->and(Reflector::isCallable(['missing', 'method']))->toBeFalse()
        ->and(Reflector::isCallable([new stdClass, 'nope']))->toBeFalse();
});

test('reflector isCallable recognizes public instance methods', function () {
    $subject = new class
    {
        public function greet(): string
        {
            return 'hi';
        }

        protected function hidden(): void {}
    };

    expect(Reflector::isCallable([$subject, 'greet']))->toBeTrue()
        ->and(Reflector::isCallable([$subject, 'hidden']))->toBeFalse();
});

test('reflector getParameterClassName resolves named types', function () {
    $fn = static function (DateTimeInterface $when, string $label, int|DateTime $mixed): void {};
    $parameters = (new ReflectionFunction($fn))->getParameters();

    expect(Reflector::getParameterClassName($parameters[0]))->toBe(DateTimeInterface::class)
        ->and(Reflector::getParameterClassName($parameters[1]))->toBeNull()
        ->and(Reflector::getParameterClassNames($parameters[2]))->toBe([DateTime::class]);
});
