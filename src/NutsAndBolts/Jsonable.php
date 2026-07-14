<?php

namespace ScrapyardIO\NutsAndBolts;

interface Jsonable
{
    /**
     * Convert the object to its JSON representation.
     */
    public function toJson(int $options = 0): string;
}
