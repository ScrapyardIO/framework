<?php

namespace Fabricate\Database\Polisher\Relations\Concerns;

use Fabricate\Database\Polisher\Model;
use Fabricate\Database\Polisher\RelationNotFoundException;
use Fabricate\NutsAndBolts\Arr;
use Fabricate\NutsAndBolts\Str;

trait SupportsInverseRelations
{
    /**
     * The name of the inverse relationship.
     *
     * @var string|null
     */
    protected ?string $inverseRelationship = null;

    /**
     * Instruct Eloquent to link the related models back to the parent after the relationship query has run.
     *
     * Alias of "chaperone".
     *
     * @param  string|null  $relation
     * @return $this
     */
    public function inverse(?string $relation = null)
    {
        return $this->chaperone($relation);
    }

    /**
     * Instruct Eloquent to link the related models back to the parent after the relationship query has run.
     *
     * @param  string|null  $relation
     * @return $this
     */
    public function chaperone(?string $relation = null)
    {
        $relation ??= $this->guessInverseRelation();

        if (! $relation || ! $this->getModel()->isRelation($relation)) {
            throw RelationNotFoundException::make($this->getModel(), $relation ?: 'null');
        }

        if ($this->inverseRelationship === null && $relation) {
            $this->query->afterQuery(function ($result) {
                return $this->inverseRelationship
                    ? $this->applyInverseRelationToCollection($result, $this->getParent())
                    : $result;
            });
        }

        $this->inverseRelationship = $relation;

        return $this;
    }

    /**
     * Guess the name of the inverse relationship.
     *
     * @return string|null
     */
    protected function guessInverseRelation(): ?string
    {
        return Arr::first(
            $this->getPossibleInverseRelations(),
            fn ($relation) => $relation && $this->getModel()->isRelation($relation)
        );
    }

    /**
     * Get the possible inverse relations for the parent model.
     *
     * @return array<non-empty-string>
     */
    protected function getPossibleInverseRelations(): array
    {
        return array_filter(array_unique([
            Str::camel(Str::beforeLast($this->getForeignKeyName(), $this->getParent()->getKeyName())),
            Str::camel(Str::beforeLast($this->getParent()->getForeignKey(), $this->getParent()->getKeyName())),
            Str::camel(class_basename($this->getParent())),
            'owner',
            get_class($this->getParent()) === get_class($this->getModel()) ? 'parent' : null,
        ]));
    }

    /**
     * Set the inverse relation on all models in a collection.
     *
     * @param  \Fabricate\Database\Polisher\Collection  $models
     * @param  \Fabricate\Database\Polisher\Model|null  $parent
     * @return \Fabricate\Database\Polisher\Collection
     */
    protected function applyInverseRelationToCollection($models, ?Model $parent = null)
    {
        $parent ??= $this->getParent();

        foreach ($models as $model) {
            $model instanceof Model && $this->applyInverseRelationToModel($model, $parent);
        }

        return $models;
    }

    /**
     * Set the inverse relation on a model.
     *
     * @param  \Fabricate\Database\Polisher\Model  $model
     * @param  \Fabricate\Database\Polisher\Model|null  $parent
     * @return \Fabricate\Database\Polisher\Model
     */
    protected function applyInverseRelationToModel(Model $model, ?Model $parent = null)
    {
        if ($inverse = $this->getInverseRelationship()) {
            $parent ??= $this->getParent();

            $model->setRelation($inverse, $parent);
        }

        return $model;
    }

    /**
     * Get the name of the inverse relationship.
     *
     * @return string|null
     */
    public function getInverseRelationship()
    {
        return $this->inverseRelationship;
    }

    /**
     * Remove the chaperone / inverse relationship for this query.
     *
     * Alias of "withoutChaperone".
     *
     * @return $this
     */
    public function withoutInverse()
    {
        return $this->withoutChaperone();
    }

    /**
     * Remove the chaperone / inverse relationship for this query.
     *
     * @return $this
     */
    public function withoutChaperone()
    {
        $this->inverseRelationship = null;

        return $this;
    }
}
