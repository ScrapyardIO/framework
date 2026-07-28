<?php

namespace Fabricate\Database\Eloquent;

use Fabricate\Database\RecordsNotFoundException;
use Fabricate\NutsAndBolts\Arr;

use function Fabricate\NutsAndBolts\Helpers\enum_value;

/**
 * @template TModel of object
 */
class ModelNotFoundException extends RecordsNotFoundException
{
    /**
     * Name of the affected model.
     *
     * @var class-string<TModel>
     */
    protected $model;

    /**
     * The affected model IDs.
     *
     * @var array<int, int|string>
     */
    protected $ids = [];

    /**
     * Set the affected model and instance ids.
     *
     * @param  class-string<TModel>  $model
     * @param  array<int, int|string>|int|string  $ids
     * @return $this
     */
    public function setModel($model, $ids = [])
    {
        $this->model = $model;

        $this->ids = array_map(enum_value(...), Arr::wrap($ids));

        $this->message = "No query results for model [{$model}]";

        if ($this->ids !== []) {
            $this->message .= ' '.implode(', ', $this->ids);
        } else {
            $this->message .= '.';
        }

        return $this;
    }

    /**
     * Get the affected model class name.
     *
     * @return class-string<TModel>
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the affected model IDs.
     *
     * @return array<int, int|string>
     */
    public function getIds()
    {
        return $this->ids;
    }
}
