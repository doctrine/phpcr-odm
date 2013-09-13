<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class ConstraintAndx extends ConstraintFactory
{
    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

    public function getCardinalityMap()
    {
        return array(
            self::NT_CONSTRAINT => array(2, 2),
        );
    }
}
