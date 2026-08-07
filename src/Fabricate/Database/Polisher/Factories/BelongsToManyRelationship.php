<?php

namespace Fabricate\Database\Polisher\Factories;

use Fabricate\Database\Polisher\Model;
use Fabricate\NutsAndBolts\Collection;

class BelongsToManyRelationship
{
    /**
     * The related factory instance.
     *
     * @var \Fabricate\Database\Polisher\Factories\Factory|\Fabricate\NutsAndBolts\Collection|\Fabricate\Database\Polisher\Model|array
     */
    protected $factory;

    /**
     * The pivot attributes / attribute resolver.
     *
     * @var callable|array
     */
    protected $pivot;

    /**
     * The relationship name.
     *
     * @var string
     */
    protected $relationship;

    /**
     * Create a new attached relationship definition.
     *
     * @param  \Fabricate\Database\Polisher\Factories\Factory|\Fabricate\NutsAndBolts\Collection|\Fabricate\Database\Polisher\Model|array  $factory
     * @param  callable|array  $pivot
     * @param  string  $relationship
     */
    public function __construct($factory, $pivot, $relationship)
    {
        $this->factory = $factory;
        $this->pivot = $pivot;
        $this->relationship = $relationship;
    }

    /**
     * Create the attached relationship for the given model.
     *
     * @param  \Fabricate\Database\Polisher\Model  $model
     * @return void
     */
    public function createFor(Model $model)
    {
        $factoryInstance = $this->factory instanceof Factory;

        if ($factoryInstance) {
            $relationship = $model->{$this->relationship}();
        }

        Collection::wrap($factoryInstance ? $this->factory->prependState($relationship->getQuery()->pendingAttributes)->create([], $model) : $this->factory)->each(function ($attachable) use ($model) {
            $model->{$this->relationship}()->attach(
                $attachable,
                is_callable($this->pivot) ? call_user_func($this->pivot, $model) : $this->pivot
            );
        });
    }

    /**
     * Specify the model instances to always use when creating relationships.
     *
     * @param  \Fabricate\NutsAndBolts\Collection  $recycle
     * @return $this
     */
    public function recycle($recycle)
    {
        if ($this->factory instanceof Factory) {
            $this->factory = $this->factory->recycle($recycle);
        }

        return $this;
    }
}
