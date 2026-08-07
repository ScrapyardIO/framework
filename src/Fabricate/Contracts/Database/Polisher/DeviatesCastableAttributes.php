<?php

namespace Fabricate\Contracts\Database\Polisher;

interface DeviatesCastableAttributes
{
    /**
     * Increment the attribute.
     *
     * @param  \Fabricate\Database\Polisher\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function increment($model, string $key, $value, array $attributes);

    /**
     * Decrement the attribute.
     *
     * @param  \Fabricate\Database\Polisher\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function decrement($model, string $key, $value, array $attributes);
}
