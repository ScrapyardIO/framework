<?php

use Fabricate\Contracts\Sketches\Attributes\Sketch as SketchAttribute;
use Fabricate\Contracts\Sketches\SketchException;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Core\Machine;
use Fabricate\Sketches\Sketch;
use Fabricate\Sketches\SketchRegistry;

#[SketchAttribute('attributed-fixture')]
class AttributedFixtureSketch extends Sketch
{
    public function loop(): SketchLoopResult
    {
        return SketchLoopResult::STOP;
    }
}

test('registry registers attributed sketches and resolves them', function () {
    $basePath = sys_get_temp_dir().'/scrapyard-io-sketch-registry-'.uniqid();
    mkdir($basePath.'/config', 0777, true);

    try {
        $app = new Machine($basePath);
        $registry = new SketchRegistry($app);

        $registry->register(AttributedFixtureSketch::class);

        expect($registry->has('attributed-fixture'))->toBeTrue()
            ->and($registry->resolve('attributed-fixture'))->toBeInstanceOf(AttributedFixtureSketch::class);
    } finally {
        destroyTempMachinePath($basePath);
    }
});

test('registry rejects duplicate names', function () {
    $basePath = sys_get_temp_dir().'/scrapyard-io-sketch-registry-dup-'.uniqid();
    mkdir($basePath.'/config', 0777, true);

    try {
        $app = new Machine($basePath);
        $registry = new SketchRegistry($app);

        $registry->registerConvention('hello', AttributedFixtureSketch::class);

        expect(fn () => $registry->registerConvention('hello', AttributedFixtureSketch::class))
            ->toThrow(SketchException::class);
    } finally {
        destroyTempMachinePath($basePath);
    }
});

#[SketchAttribute('replace-fixture')]
class ReplaceFixtureSketch extends Sketch
{
    public function loop(): SketchLoopResult
    {
        return SketchLoopResult::STOP;
    }
}

test('registry replace overwrites an existing sketch name', function () {
    $basePath = sys_get_temp_dir().'/scrapyard-io-sketch-registry-replace-'.uniqid();
    mkdir($basePath.'/config', 0777, true);

    try {
        $app = new Machine($basePath);
        $registry = new SketchRegistry($app);

        $registry->registerConvention('replace-fixture', AttributedFixtureSketch::class);
        $registry->replace(ReplaceFixtureSketch::class);

        expect($registry->all()['replace-fixture'])->toBe(ReplaceFixtureSketch::class)
            ->and($registry->resolve('replace-fixture'))->toBeInstanceOf(ReplaceFixtureSketch::class);
    } finally {
        destroyTempMachinePath($basePath);
    }
});

test('registry replaceAs binds under an explicit name', function () {
    $basePath = sys_get_temp_dir().'/scrapyard-io-sketch-registry-replace-as-'.uniqid();
    mkdir($basePath.'/config', 0777, true);

    try {
        $app = new Machine($basePath);
        $registry = new SketchRegistry($app);

        $registry->registerConvention('canvas-window-demo', AttributedFixtureSketch::class);
        $registry->replaceAs('canvas-window-demo', ReplaceFixtureSketch::class);

        expect($registry->all()['canvas-window-demo'])->toBe(ReplaceFixtureSketch::class);
    } finally {
        destroyTempMachinePath($basePath);
    }
});
