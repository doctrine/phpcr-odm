<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

/**
 * Factory/node class for dynamic all operands.
 *
 * Extends OperandDynamicFactory, and adds the static operands.
 *
 * Traits would be really useful here.
 *
 * @IgnoreAnnotation("factoryMethod")
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class OperandFactory extends OperandDynamicFactory
{
    /**
     * Static operand: Resolves to the value of the variable bound to the given $name
     *
     * Relates to PHPCR BindVariableValueInterface
     *
     * @param string $name
     * @factoryMethod
     * @return OperandStaticParameter
     */
    public function parameter($name)
    {
        return $this->addChild(new OperandStaticParameter($this, $name));
    }

    /**
     * Static operand: Resolves to the given literal value
     *
     * @param string $value
     * @factoryMethod
     * @return OperandStaticLiteral
     */
    public function literal($value)
    {
        return $this->addChild(new OperandStaticLiteral($this, $value));
    }
}
