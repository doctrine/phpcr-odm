<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class ConstraintOrx extends ConstraintFactory
{
    public function getCardinalityMap()
    {
        return array(
            self::NT_CONSTRAINT => array(2, 2),
        );
    }

    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

}
