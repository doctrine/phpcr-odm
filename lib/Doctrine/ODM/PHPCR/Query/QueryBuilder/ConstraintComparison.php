<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class ConstraintComparison extends AbstractNode implements
    ConstraintInterface
{
    protected $operator;

    public function getCardinalityMap()
    {
        return array(
            'OperandDynamicFactory' => array('1', '1'),
            'OperandStaticFactory' => array('1', '1')
        );
    }

    public function __construct(AbstractNode $parent, $operator)
    {
        $this->operator = $operator;
        parent::__construct($parent);
    }

    /**
     * Left Operand
     *
     * @return OperandDynamicFactory
     */
    public function lop()
    {
        return $this->addChild(new OperandDynamicFactory($this));
    }

    /**
     * Right Operand
     *
     * @return OperandStaticFactory
     */
    public function rop()
    {
        return $this->addChild(new OperandStaticFactory($this));
    }

    public function getOperator()
    {
        return $this->operator;
    }
}
