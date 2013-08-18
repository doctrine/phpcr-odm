<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class ConstraintAndx extends ConstraintFactory implements
    ConstraintInterface
{
    public function getCardinalityMap()
    {
        return array(
            'ConstraintInterface' => array(2, 2),
        );
    }
}
