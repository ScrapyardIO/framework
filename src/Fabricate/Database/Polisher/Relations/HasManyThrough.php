<?php

namespace Fabricate\Database\Polisher\Relations;

use Fabricate\Database\Polisher\Builder;
use Fabricate\Database\Polisher\Collection as EloquentCollection;
use Fabricate\Database\Polisher\Relations\Concerns\InteractsWithDictionary;

/**
 * @template TRelatedModel of \Fabricate\Database\Polisher\Model
 * @template TIntermediateModel of \Fabricate\Database\Polisher\Model
 * @template TDeclaringModel of \Fabricate\Database\Polisher\Model
 *
 * @extends \Fabricate\Database\Polisher\Relations\HasOneOrManyThrough<TRelatedModel, TIntermediateModel, TDeclaringModel, \Fabricate\Database\Polisher\Collection<int, TRelatedModel>>
 */
class HasManyThrough extends HasOneOrManyThrough
{
    use InteractsWithDictionary;

    /**
     * Convert the relationship to a "has one through" relationship.
     *
     * @return \Fabricate\Database\Polisher\Relations\HasOneThrough<TRelatedModel, TIntermediateModel, TDeclaringModel>
     */
    public function one()
    {
        return HasOneThrough::noConstraints(fn () => new HasOneThrough(
            tap($this->getQuery(), fn (Builder $query) => $query->getQuery()->joins = []),
            $this->farParent,
            $this->throughParent,
            $this->getFirstKeyName(),
            $this->getForeignKeyName(),
            $this->getLocalKeyName(),
            $this->getSecondLocalKeyName(),
        ));
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
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {
            $key = $this->getDictionaryKey($model->getAttribute($this->localKey));

            if ($key !== null && isset($dictionary[$key])) {
                $model->setRelation(
                    $relation, $this->related->newCollection($dictionary[$key])
                );
            }
        }

        return $models;
    }

    /** @inheritDoc */
    public function getResults()
    {
        return ! is_null($this->farParent->{$this->localKey})
            ? $this->get()
            : $this->related->newCollection();
    }
}
