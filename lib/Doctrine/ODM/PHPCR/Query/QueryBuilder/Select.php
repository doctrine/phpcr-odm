<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class Select extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            'Column' => array(0, null)
        );
    }
}

