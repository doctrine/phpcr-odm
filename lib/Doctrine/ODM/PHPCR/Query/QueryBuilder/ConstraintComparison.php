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

    public function left()
    {
        return $this->addChild(new OperandDynamicFactory($this));
    }

    public function right()
    {
        return $this->addChild(new OperandStaticFactory($this));
    }
}


