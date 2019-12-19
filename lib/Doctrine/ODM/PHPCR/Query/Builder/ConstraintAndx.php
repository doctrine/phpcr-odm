<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Contraint which evaluates to true when
 * all its child constraints evaluate to true.
 *
 * If only a single constraint is appended, the appended
 * constraint will behave as if it were not preceded by
 * the ConstraintAndX.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ConstraintAndx extends ConstraintFactory
{
    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

    public function getCardinalityMap()
    {
        return [
            self::NT_CONSTRAINT => [1, null],
        ];
    }
}
