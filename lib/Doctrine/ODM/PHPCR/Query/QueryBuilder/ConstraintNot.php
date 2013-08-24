<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class ConstraintNot extends ConstraintFactory implements 
    ConstraintInterface
{
    public function getCardinalityMap()
    {
        return array(
            'ConstraintInterface' => array(1, 1),
        );
    }
}
