<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class ConstraintComparison extends AbstractNode
{
    protected $operator;

    public function getCardinalityMap()
    {
        return array(
            self::NT_OPERAND_DYNAMIC_FACTORY => array('1', '1'),
            self::NT_OPERAND_STATIC_FACTORY => array('1', '1'),
        );
    }

    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
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
