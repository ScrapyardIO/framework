<?php

namespace Fabricate\Contracts\Database;

class ModelIdentifier
{
    /**
     * Use a morph map for a Model's name when serializing.
     */
    protected static bool $useMorphMap = false;

    /**
     * The class name of the model.
     *
     * @var class-string|string|null
     */
    public $class;

    /**
     * The unique identifier of the model.
     *
     * @var mixed
     */
    public $id;

    /**
     * The relationships loaded on the model.
     *
     * @var array
     */
    public $relations;

    /**
     * The connection name of the model.
     *
     * @var string|null
     */
    public $connection;

    /**
     * The class name of the model collection.
     *
     * @var class-string|null
     */
    public $collectionClass;

    /**
     * Create a new model identifier.
     *
     * @param  class-string|null  $class
     * @param  mixed  $id
     * @param  array  $relations
     * @param  mixed  $connection
     */
    public function __construct($class, $id, array $relations, $connection)
    {
        $this->class = $class;
        $this->id = $id;
        $this->relations = $relations;
        $this->connection = $connection;
    }

    /**
     * Specify the collection class that should be used when serializing / restoring collections.
     *
     * @param  class-string|null  $collectionClass
     * @return $this
     */
    public function useCollectionClass(?string $collectionClass)
    {
        $this->collectionClass = $collectionClass;

        return $this;
    }

    /**
     * Get the fully-qualified class name of the Model.
     *
     * @return class-string|null
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * Indicate whether to use a relational morph-map when serializing Models.
     */
    public static function useMorphMap(bool $useMorphMap = true): void
    {
        static::$useMorphMap = $useMorphMap;
    }
}
