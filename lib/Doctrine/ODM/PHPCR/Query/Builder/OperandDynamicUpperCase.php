<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Operand which evaluates to the upper case version of its child operand.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class OperandDynamicUpperCase extends OperandDynamicFactory
{
    public function getCardinalityMap(): array
    {
        return [
            self::NT_OPERAND_DYNAMIC => [1, 1],    // 1..*
        ];
    }

    public function getNodeType(): string
    {
        return self::NT_OPERAND_DYNAMIC;
    }
}
