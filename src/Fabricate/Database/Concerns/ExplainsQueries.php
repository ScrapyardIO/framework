<?php

namespace Fabricate\Database\Concerns;

use Fabricate\NutsAndBolts\Collection;

trait ExplainsQueries
{
    /**
     * Explains the query.
     *
     * @return \Fabricate\NutsAndBolts\Collection
     */
    public function explain()
    {
        $sql = $this->toSql();

        $bindings = $this->getBindings();

        $explanation = $this->getConnection()->select('EXPLAIN '.$sql, $bindings);

        return new Collection($explanation);
    }
}
