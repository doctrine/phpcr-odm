<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;

class SourceJoin extends Source
{
    public function __construct($parent)
    {
        parent::__construct($parent);
    }

    public function getCardinalityMap()
    {
        return array(
            'Source' => array(2, 2),
            'JoinCondition' => array(1, 1),
        );
    }
}

