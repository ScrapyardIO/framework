<?php

namespace Fabricate\Graph\Database\Query;

use Fabricate\Database\Query\Builder;

class Neo4jQueryBuilder extends Builder
{
    /**
     * The Neo4j label currently targeted (Laravel "from" table).
     *
     * @var string|null
     */
    public $from;
}
