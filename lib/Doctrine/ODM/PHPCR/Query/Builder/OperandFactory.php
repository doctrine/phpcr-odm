<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

/**
 * Factory node for all operands, both dynamic and static.
 *
 * @IgnoreAnnotation("factoryMethod")
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class OperandFactory extends OperandDynamicFactory
{
    /**
     * Evaluates to the value of the parameter bound to the given $name.
     *
     * Relates to PHPCR BindVariableValueInterface
     *
     * <code>
     * $qb->where()->eq()->field('f.foobar')->parameter('param_1');
     * $qb->setParameter('param_1', 'foo');
     * </code>
     *
     * @param string $name - Name of parameter to resolve.
     *
     * @factoryMethod OperandStaticParameter
     * @return OperandFactory
     */
    public function parameter($name)
    {
        return $this->addChild(new OperandStaticParameter($this, $name));
    }

    /**
     * Evaluates to the given literal value.
     *
     * <code>
     * $qb->where()->eq()->field('f.foobar')->litreal('Literal Value');
     * </code>
     *
     * @param string $value - Literal value.
     *
     * @factoryMethod OperandStaticLiteral
     * @return OperandStaticLiteral
     */
    public function literal($value)
    {
        return $this->addChild(new OperandStaticLiteral($this, $value));
    }
}
