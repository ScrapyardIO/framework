<?php

namespace Fabricate\Database\Polisher\Relations;

use Fabricate\Database\Polisher\Collection as EloquentCollection;

/**
 * @template TRelatedModel of \Fabricate\Database\Polisher\Model
 * @template TDeclaringModel of \Fabricate\Database\Polisher\Model
 *
 * @extends \Fabricate\Database\Polisher\Relations\HasOneOrMany<TRelatedModel, TDeclaringModel, \Fabricate\Database\Polisher\Collection<int, TRelatedModel>>
 */
class HasMany extends HasOneOrMany
{
    /**
     * Convert the relationship to a "has one" relationship.
     *
     * @return \Fabricate\Database\Polisher\Relations\HasOne<TRelatedModel, TDeclaringModel>
     */
    public function one()
    {
        return HasOne::noConstraints(fn () => tap(
            new HasOne(
                $this->getQuery(),
                $this->parent,
                $this->foreignKey,
                $this->localKey
            ),
            function ($hasOne) {
                if ($inverse = $this->getInverseRelationship()) {
                    $hasOne->inverse($inverse);
                }
            }
        ));
    }

    /** @inheritDoc */
    public function getResults()
    {
        return ! is_null($this->getParentKey())
            ? $this->query->get()
            : $this->related->newCollection();
    }

    /** @inheritDoc */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /** @inheritDoc */
    public function match(array $models, EloquentCollection $results, $relation)
    {
        return $this->matchMany($models, $results, $relation);
    }
}
