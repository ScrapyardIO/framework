<?php

use Fabricate\JsonSchema\JsonSchema;
use Fabricate\JsonSchema\Types\ObjectType;

test('object schema serializes to array and round-trips via fromArray', function () {
    $schema = JsonSchema::object([
        'name' => JsonSchema::string()->required(),
        'age' => JsonSchema::integer()->min(1)->max(120)->required(),
        'nickname' => JsonSchema::string()->nullable(),
    ])->title('User');

    expect($schema)->toBeInstanceOf(ObjectType::class);

    $array = $schema->toArray();

    expect($array)->toMatchArray([
        'type' => 'object',
        'title' => 'User',
        'required' => ['name', 'age'],
    ])
        ->and($array['properties']['name'])->toMatchArray([
            'type' => 'string',
        ])
        ->and($array['properties']['age'])->toMatchArray([
            'type' => 'integer',
            'minimum' => 1,
            'maximum' => 120,
        ])
        ->and($array['properties']['nickname'])->toMatchArray([
            'type' => ['string', 'null'],
        ]);

    $roundTrip = JsonSchema::fromArray($array);

    expect($roundTrip->toArray())->toBe($array);
});

test('anyOf schema serializes and round-trips via fromArray', function () {
    $schema = JsonSchema::anyOf([
        JsonSchema::object([
            'type' => JsonSchema::string()->enum(['credit_card'])->required(),
            'card_number' => JsonSchema::string()->required(),
        ]),
        JsonSchema::object([
            'type' => JsonSchema::string()->enum(['bank_transfer'])->required(),
            'account_number' => JsonSchema::string()->required(),
        ]),
    ])->title('PaymentMethod');

    $array = $schema->toArray();

    expect($array)->toHaveKey('anyOf')
        ->and($array['anyOf'])->toHaveCount(2);

    expect(JsonSchema::fromArray($array)->toArray())->toBe($array);
});

test('union type serializes and round-trips via fromArray', function () {
    $schema = JsonSchema::union(['string', 'integer'])->required();

    $array = $schema->toArray();

    expect($array['type'])->toBe(['string', 'integer']);

    expect(JsonSchema::fromArray($array)->toArray())->toBe($array);
});
