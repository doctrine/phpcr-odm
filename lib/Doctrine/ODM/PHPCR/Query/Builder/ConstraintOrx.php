<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Constraint which evaluates to true if any one of its children
 * evaluates to true.
 *
 * Like the ConstraintAndx constraint a single child will act as if
 * it were not preceded with a ConstraintOrx.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ConstraintOrx extends ConstraintFactory
{
    public function getCardinalityMap(): array
    {
        return [
            self::NT_CONSTRAINT => [1, null],
        ];
    }

    public function getNodeType(): string
    {
        return self::NT_CONSTRAINT;
    }
}
