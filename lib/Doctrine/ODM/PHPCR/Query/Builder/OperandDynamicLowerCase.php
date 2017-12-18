<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Dynamic operand which evaluates to the lowercased value of the child operand.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class OperandDynamicLowerCase extends OperandDynamicFactory
{
    public function getCardinalityMap()
    {
        return [
            self::NT_OPERAND_DYNAMIC => [1, 1],    // 1..*
        ];
    }

    public function getNodeType()
    {
        return self::NT_OPERAND_DYNAMIC;
    }
}
