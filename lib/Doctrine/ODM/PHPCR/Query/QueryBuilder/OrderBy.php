<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class OrderBy extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            'Ordering' => array(0, null)
        );
    }
}


