<?php

namespace Fabricate\Database\Polisher;

interface Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @template TModel of \Fabricate\Database\Polisher\Model
     *
     * @param  \Fabricate\Database\Polisher\Builder<TModel>  $builder
     * @param  TModel  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model);
}
