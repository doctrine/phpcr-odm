<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Query;

interface ConverterInterface
{
    /**
     * Returns an ODM Query object from the given ODM (query) Builder.
     *
     * Dispatches the From, Select, Where and OrderBy nodes. Each of these
     * "root" nodes append or set PHPCR QOM objects to corresponding properties
     * in this class, which are subsequently used to create a PHPCR QOM object which
     * is embedded in an ODM Query object.
     */
    public function getQuery(QueryBuilder $queryBuilder): Query;
}
