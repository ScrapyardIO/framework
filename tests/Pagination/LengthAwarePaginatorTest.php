<?php

use Fabricate\Pagination\LengthAwarePaginator;

test('length aware paginator reports totals and pages', function () {
    $paginator = new LengthAwarePaginator(
        items: ['a', 'b', 'c'],
        total: 30,
        perPage: 10,
        currentPage: 2,
        options: ['path' => '/items'],
    );

    expect($paginator->total())->toBe(30)
        ->and($paginator->perPage())->toBe(10)
        ->and($paginator->currentPage())->toBe(2)
        ->and($paginator->lastPage())->toBe(3)
        ->and($paginator->hasMorePages())->toBeTrue()
        ->and($paginator->count())->toBe(3)
        ->and($paginator->items())->toBe(['a', 'b', 'c']);
});

test('length aware paginator toArray includes meta', function () {
    $paginator = new LengthAwarePaginator(
        items: [1, 2],
        total: 2,
        perPage: 15,
        currentPage: 1,
        options: ['path' => 'http://localhost/widgets'],
    );

    $payload = $paginator->toArray();

    expect($payload['total'])->toBe(2)
        ->and($payload['per_page'])->toBe(15)
        ->and($payload['current_page'])->toBe(1)
        ->and($payload['last_page'])->toBe(1)
        ->and($payload['data'])->toBe([1, 2]);
});
