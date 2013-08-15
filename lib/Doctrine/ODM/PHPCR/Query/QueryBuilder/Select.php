<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class Select extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            'Property' => array(0, null)
        );
    }
}

