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
     * Resolves to the value of the variable bound to the given $name.
     *
     * Relates to PHPCR BindVariableValueInterface
     *
     * <code>
     * $qb->where()->eq()->field('f.foobar')->parameter('param_1');
     * </code>
     *
     * @param string $name - Name of parameter to resolve.
     *
     * @factoryMethod
     * @return OperandFactory
     */
    public function parameter($name)
    {
        return $this->addChild(new OperandStaticParameter($this, $name));
    }

    /**
     * Resolves to the given literal value.
     *
     * <code>
     * $qb->where()->eq()->field('f.foobar')->litreal('Literal Value');
     * </code>
     *
     * @param string $value - Literal value.
     *
     * @factoryMethod
     * @return OperandStaticLiteral
     */
    public function literal($value)
    {
        return $this->addChild(new OperandStaticLiteral($this, $value));
    }
}
