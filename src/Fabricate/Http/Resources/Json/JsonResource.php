<?php

namespace Fabricate\Http\Resources\Json;

class JsonResource
{
    public function __construct(public mixed $resource) {}
    public static function make(...$parameters): static { return new static(...$parameters); }
    public static function collection($resource): array { return array_map(static::make(...), $resource->all()); }
}
