<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

/**
 * Factory/node class for dynamic all operands.
 *
 * Extends OperandDynamicFactory, and adds the static operands.
 *
 * Traits would be really useful here.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class OperandFactory extends OperandDynamicFactory
{
    /**
     * Static operand: Resolves to the value of the variable bound to the given $name
     *
     * @param string $name
     */
    public function bindVariable($name)
    {
        return $this->addChild(new OperandStaticBindVariable($this, $name));
    }

    /**
     * Static operand: Resolves to the given literal value
     *
     * @param string $value
     */
    public function literal($value)
    {
        return $this->addChild(new OperandStaticLiteral($this, $value));
    }
}
