<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class ConstraintComparison extends OperandFactory
{
    protected $operator;

    public function getCardinalityMap()
    {
        return array(
            self::NT_OPERAND_DYNAMIC => array('1', '1'),
            self::NT_OPERAND_STATIC => array('1', '1'),
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

    public function getOperator()
    {
        return $this->operator;
    }
}
