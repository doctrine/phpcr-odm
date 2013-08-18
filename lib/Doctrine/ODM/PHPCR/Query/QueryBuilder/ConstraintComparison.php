<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class ConstraintComparison extends AbstractNode implements
    ConstraintInterface
{
    protected $operator;

    public function getCardinalityMap()
    {
        return array(
            'OperandInterface' => '2', '2'
        );
    }

    public function __construct(AbstractNode $parent, $operator)
    {
        $this->operator = $operator;
        parent::__construct($parent);
    }
}


