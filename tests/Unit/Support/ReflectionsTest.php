<?php

use DeptOfScrapyardRobotics\Tests\Fixtures\Reflection\AttributedClass;
use DeptOfScrapyardRobotics\Tests\Fixtures\Reflection\AttributedMethod;
use DeptOfScrapyardRobotics\Tests\Fixtures\Reflection\AttributedProperty;
use DeptOfScrapyardRobotics\Tests\Fixtures\Reflection\Namespaced\AlphaFixture;
use DeptOfScrapyardRobotics\Tests\Fixtures\Reflection\Namespaced\BetaFixture;
use DeptOfScrapyardRobotics\Tests\Fixtures\Reflection\Namespaced\Nested\GammaFixture;
use DeptOfScrapyardRobotics\Tests\Fixtures\Reflection\Packages\Alpha\AlphaPackageFixture;
use DeptOfScrapyardRobotics\Tests\Fixtures\Reflection\Packages\Beta\Nested\BetaPackageFixture;
use DeptOfScrapyardRobotics\Tests\Fixtures\Reflection\PlainClass;
use DeptOfScrapyardRobotics\Tests\Fixtures\Reflection\PlainProperty;
use DeptOfScrapyardRobotics\Tests\Fixtures\Reflection\ReflectableAttribute;
use ScrapyardIO\NutsAndBolts\Reflection;

// The Packages/* fixtures intentionally use folder names that don't mirror
// their namespaces (see classes_in_packages_directory tests below), so they
// sit outside any real psr-4 mapping and must be loaded explicitly here
// instead of relying on autoloading.
require_once __DIR__.'/../../Fixtures/Reflection/Packages/alpha/src/AlphaPackageFixture.php';
require_once __DIR__.'/../../Fixtures/Reflection/Packages/beta-weird-folder-name/lib/BetaPackageFixture.php';
require_once __DIR__.'/../../Fixtures/Reflection/Packages/gamma-no-composer/src/GammaPackageFixture.php';

// classes_in_namespace

test('classes_in_namespace finds classes recursively and ignores files without a matching class', function () {
    $result = Reflection::classes_in_namespace(
        'DeptOfScrapyardRobotics\\Tests\\Fixtures\\Reflection\\Namespaced',
        __DIR__.'/../../Fixtures/Reflection/Namespaced'
    );

    expect($result)
        ->toBeArray()
        ->toHaveCount(3)
        ->toContain(AlphaFixture::class)
        ->toContain(BetaFixture::class)
        ->toContain(GammaFixture::class);
});

test('classes_in_namespace returns an empty array when no classes are found', function () {
    $result = Reflection::classes_in_namespace(
        'DeptOfScrapyardRobotics\\Tests\\Fixtures\\Reflection\\Empty',
        __DIR__.'/../../Fixtures/Reflection/Empty'
    );

    expect($result)->toBe([]);
});

test('classes_in_namespace trims trailing separators from the namespace and directory', function () {
    $result = Reflection::classes_in_namespace(
        'DeptOfScrapyardRobotics\\Tests\\Fixtures\\Reflection\\Namespaced\\',
        __DIR__.'/../../Fixtures/Reflection/Namespaced/'
    );

    expect($result)->toHaveCount(3);
});

// classes_in_packages_directory

test('classes_in_packages_directory discovers classes across sibling packages using each own composer.json psr-4 map', function () {
    $result = Reflection::classes_in_packages_directory(__DIR__.'/../../Fixtures/Reflection/Packages');

    expect($result)
        ->toBeArray()
        ->toHaveCount(2)
        ->toContain(AlphaPackageFixture::class)
        ->toContain(BetaPackageFixture::class);
});

test('classes_in_packages_directory skips package folders without a composer.json', function () {
    $result = Reflection::classes_in_packages_directory(__DIR__.'/../../Fixtures/Reflection/Packages');

    expect($result)->not->toContain('DeptOfScrapyardRobotics\\Tests\\Fixtures\\Reflection\\Packages\\Gamma\\GammaPackageFixture');
});

test('classes_in_packages_directory does not depend on package folder names matching namespaces', function () {
    // The "beta-weird-folder-name" fixture package intentionally has a folder
    // name unrelated to its namespace (Packages\Beta\Nested) and psr-4 root
    // (lib/ instead of src/), proving discovery relies on composer.json, not
    // folder-name guessing.
    $result = Reflection::classes_in_packages_directory(__DIR__.'/../../Fixtures/Reflection/Packages');

    expect($result)->toContain(BetaPackageFixture::class);
});

test('classes_in_packages_directory returns an empty array when the directory has no packages', function () {
    expect(Reflection::classes_in_packages_directory(__DIR__.'/../../Fixtures/Reflection/Empty'))->toBe([]);
});

// reflect_property

test('reflect_property finds a property carrying the given attribute', function () {
    $result = Reflection::reflect_property(new AttributedProperty, ReflectableAttribute::class);

    expect($result)
        ->not->toBeNull()
        ->and($result->getName())
        ->toBe('marked')
        ->and($result->getAttributes(ReflectableAttribute::class)[0]->newInstance()->value)
        ->toBe('marked-property');
});

test('reflect_property returns null when no property carries the given attribute', function () {
    expect(Reflection::reflect_property(new PlainProperty, ReflectableAttribute::class))->toBeNull();
});

// reflect_class

test('reflect_class finds a class-level attribute on an object instance', function () {
    $result = Reflection::reflect_class(new AttributedClass, ReflectableAttribute::class);

    expect($result)
        ->not->toBeNull()
        ->and($result->newInstance()->value)
        ->toBe('marked-class');
});

test('reflect_class finds a class-level attribute given a class name string', function () {
    $result = Reflection::reflect_class(AttributedClass::class, ReflectableAttribute::class);

    expect($result)
        ->not->toBeNull()
        ->and($result->newInstance()->value)
        ->toBe('marked-class');
});

test('reflect_class returns null when the class has no matching attribute', function () {
    expect(Reflection::reflect_class(PlainClass::class, ReflectableAttribute::class))->toBeNull();
});

test('reflect_class throws for an unknown class name', function () {
    Reflection::reflect_class('NotARealClass', ReflectableAttribute::class);
})->throws(ReflectionException::class);

// reflect_parameter

test('reflect_parameter finds a parameter carrying the given attribute', function () {
    $result = Reflection::reflect_parameter(AttributedMethod::class, 'withAttribute', ReflectableAttribute::class);

    expect($result)
        ->not->toBeNull()
        ->and($result->getName())
        ->toBe('marked')
        ->and($result->getAttributes(ReflectableAttribute::class)[0]->newInstance()->value)
        ->toBe('marked-parameter');
});

test('reflect_parameter returns null when no parameter carries the given attribute', function () {
    $result = Reflection::reflect_parameter(AttributedMethod::class, 'withoutAttribute', ReflectableAttribute::class);

    expect($result)->toBeNull();
});

test('reflect_parameter throws for an unknown method name', function () {
    Reflection::reflect_parameter(AttributedMethod::class, 'notARealMethod', ReflectableAttribute::class);
})->throws(ReflectionException::class);
