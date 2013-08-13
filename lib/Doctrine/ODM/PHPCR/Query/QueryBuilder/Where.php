<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class Where extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            'Constraint' => array(1, 1),
        );
    }
}
