<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

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
     * Relates to PHPCR BindVariableValueInterface::
     *
     * <code>
     * $qb->where()->eq()->field('f.foobar')->parameter('param_1')->end();
     * $qb->setParameter('param_1', 'foo');
     * </code>
     *
     * @param string $name The parameter name to resolve
     *
     * @factoryMethod OperandStaticParameter
     */
    public function parameter(string $name): AbstractNode
    {
        return $this->addChild(new OperandStaticParameter($this, $name));
    }

    /**
     * Evaluates to the given literal value::.
     *
     * <code>
     * $qb->where()->eq()->field('f.foobar')->literal('Literal Value')->end();
     * </code>
     *
     * @param string $value Literal value
     *
     * @factoryMethod OperandStaticLiteral
     */
    public function literal($value): AbstractNode
    {
        return $this->addChild(new OperandStaticLiteral($this, $value));
    }
}
