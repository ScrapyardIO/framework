<?php

namespace Fabricate\Database\Polisher;

use BadMethodCallException;
use Fabricate\Database\Polisher\Relations\HasMany;
use Fabricate\Database\Polisher\Relations\MorphOneOrMany;
use Fabricate\NutsAndBolts\Str;
use Fabricate\NutsAndBolts\Stringable;

/**
 * @template TIntermediateModel of \Fabricate\Database\Polisher\Model
 * @template TDeclaringModel of \Fabricate\Database\Polisher\Model
 * @template TLocalRelationship of \Fabricate\Database\Polisher\Relations\HasOneOrMany<TIntermediateModel, TDeclaringModel>
 */
class PendingHasThroughRelationship
{
    /**
     * The root model that the relationship exists on.
     *
     * @var TDeclaringModel
     */
    protected $rootModel;

    /**
     * The local relationship.
     *
     * @var TLocalRelationship
     */
    protected $localRelationship;

    /**
     * Create a pending has-many-through or has-one-through relationship.
     *
     * @param  TDeclaringModel  $rootModel
     * @param  TLocalRelationship  $localRelationship
     */
    public function __construct($rootModel, $localRelationship)
    {
        $this->rootModel = $rootModel;

        $this->localRelationship = $localRelationship;
    }

    /**
     * Define the distant relationship that this model has.
     *
     * @template TRelatedModel of \Fabricate\Database\Polisher\Model
     *
     * @param  string|(callable(TIntermediateModel): (\Fabricate\Database\Polisher\Relations\HasOne<TRelatedModel, TIntermediateModel>|\Fabricate\Database\Polisher\Relations\HasMany<TRelatedModel, TIntermediateModel>|\Fabricate\Database\Polisher\Relations\MorphOneOrMany<TRelatedModel, TIntermediateModel>))  $callback
     * @return (
     *     $callback is string
     *     ? \Fabricate\Database\Polisher\Relations\HasManyThrough<\Fabricate\Database\Polisher\Model, TIntermediateModel, TDeclaringModel>|\Fabricate\Database\Polisher\Relations\HasOneThrough<\Fabricate\Database\Polisher\Model, TIntermediateModel, TDeclaringModel>
     *     : (
     *         TLocalRelationship is \Fabricate\Database\Polisher\Relations\HasMany<TIntermediateModel, TDeclaringModel>
     *         ? \Fabricate\Database\Polisher\Relations\HasManyThrough<TRelatedModel, TIntermediateModel, TDeclaringModel>
     *         : (
     *              $callback is callable(TIntermediateModel): \Fabricate\Database\Polisher\Relations\HasMany<TRelatedModel, TIntermediateModel>
     *              ? \Fabricate\Database\Polisher\Relations\HasManyThrough<TRelatedModel, TIntermediateModel, TDeclaringModel>
     *              : \Fabricate\Database\Polisher\Relations\HasOneThrough<TRelatedModel, TIntermediateModel, TDeclaringModel>
     *         )
     *     )
     * )
     */
    public function has($callback)
    {
        if (is_string($callback)) {
            $callback = fn () => $this->localRelationship->getRelated()->{$callback}();
        }

        $distantRelation = $callback($this->localRelationship->getRelated());

        if ($distantRelation instanceof HasMany || $this->localRelationship instanceof HasMany) {
            $returnedRelation = $this->rootModel->hasManyThrough(
                $distantRelation->getRelated()::class,
                $this->localRelationship->getRelated()::class,
                $this->localRelationship->getForeignKeyName(),
                $distantRelation->getForeignKeyName(),
                $this->localRelationship->getLocalKeyName(),
                $distantRelation->getLocalKeyName(),
            );
        } else {
            $returnedRelation = $this->rootModel->hasOneThrough(
                $distantRelation->getRelated()::class,
                $this->localRelationship->getRelated()::class,
                $this->localRelationship->getForeignKeyName(),
                $distantRelation->getForeignKeyName(),
                $this->localRelationship->getLocalKeyName(),
                $distantRelation->getLocalKeyName(),
            );
        }

        if ($this->localRelationship instanceof MorphOneOrMany) {
            $returnedRelation->where($this->localRelationship->getQualifiedMorphType(), $this->localRelationship->getMorphClass());
        }

        return $returnedRelation;
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (Str::startsWith($method, 'has')) {
            return $this->has((new Stringable($method))->after('has')->lcfirst()->toString());
        }

        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }
}
