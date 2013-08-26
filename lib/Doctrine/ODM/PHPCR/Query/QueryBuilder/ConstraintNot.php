<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class ConstraintNot extends ConstraintFactory
{
    public function getCardinalityMap()
    {
        return array(
            self::NT_CONSTRAINT => array(1, 1),
        );
    }

    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

}
