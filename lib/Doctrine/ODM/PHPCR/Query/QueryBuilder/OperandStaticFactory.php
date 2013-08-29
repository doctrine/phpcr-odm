<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

/**
 * Factory/node class for static operands.
 *
 * As the name suggests, static operand values do
 * not change once initialized and are used as the "right hand
 * side" operands in comparisons.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class OperandStaticFactory extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            self::NT_OPERAND_STATIC => array(1, 1),
        );
    }

    public function getNodeType()
    {
        return self::NT_OPERAND_FACTORY;
    }

    public function bindVariable($name)
    {
        return $this->addChild(new OperandStaticBindVariable($this, $name));
    }

    public function literal($value)
    {
        return $this->addChild(new OperandStaticLiteral($this, $value));
    }
}
