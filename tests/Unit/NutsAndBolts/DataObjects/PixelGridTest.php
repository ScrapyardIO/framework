<?php

use ScrapyardIO\NutsAndBolts\DataObjects\PixelGrid;

dataset('out-of-bounds coordinates', [
    'negative x' => [-1, 0],
    'negative y' => [0, -1],
    'x at width' => [4, 0],
    'y at height' => [0, 3],
]);

it('complains about the width being invalid', function () {
    new PixelGrid(0, 4);
})->throws(InvalidArgumentException::class);

it('complains about the height being invalid', function () {
    new PixelGrid(4, 0);
})->throws(InvalidArgumentException::class);

it('contains all zeros when init by default', function () {
    $grid = new PixelGrid(2, 2);

    expect($grid->values())->toBe([0, 0, 0, 0]);
});

it('fills the array on init', function () {
    $grid = new PixelGrid(2, 2, 7);

    expect($grid->values())->toBe([7, 7, 7, 7]);
});

it('fills the array on-demand', function () {
    $grid = new PixelGrid(2, 2);

    expect($grid->fill(5))->toBe($grid)
        ->and($grid->values())->toBe([5, 5, 5, 5]);
});

it('clears the array on-demand', function () {
    $grid = new PixelGrid(2, 2, 9);

    expect($grid->clear())->toBe($grid)
        ->and($grid->values())->toBe([0, 0, 0, 0]);
});

it('sets a specific cell value that I set and returns itself', function () {
    $grid = new PixelGrid(4, 3);

    expect($grid->set(2, 1, 0xFF))->toBe($grid);
});

it('complains when you set invalid coordinates', function (int $x, int $y) {
    (new PixelGrid(4, 3))->set($x, $y, 0xFF);
})->throws(OutOfBoundsException::class)->with('out-of-bounds coordinates');

it('complains when you ask for invalid coordinates', function (int $x, int $y) {
    (new PixelGrid(4, 3))->get($x, $y);
})->throws(OutOfBoundsException::class)->with('out-of-bounds coordinates');

it('has the value I set within a specific cell', function () {
    $grid = new PixelGrid(4, 3);

    $grid->set(2, 1, 0xFF);

    expect($grid->values()[(1 * 4) + 2])->toBe(0xFF);
});

it('returns the value I set within a specific cell', function () {
    $grid = new PixelGrid(4, 3);

    $grid->set(2, 1, 0xFF);

    expect($grid->get(2, 1))->toBe(0xFF);
});

it('returns the array in a 2D form', function () {
    $grid = new PixelGrid(3, 2);

    $grid->set(2, 0, 1)->set(0, 1, 2);

    expect($grid->toArray())->toBe([
        [0, 0, 1],
        [2, 0, 0],
    ]);
});

it('returns the array in its rawest, linear form', function () {
    $grid = new PixelGrid(3, 2);

    $grid->set(2, 0, 1)->set(0, 1, 2);

    expect($grid->values())->toBe([0, 0, 1, 2, 0, 0]);
});
