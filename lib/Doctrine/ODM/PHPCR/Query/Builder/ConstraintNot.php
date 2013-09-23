<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Constraint which evaluates to the opposite truth of its child
 * operand.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
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
