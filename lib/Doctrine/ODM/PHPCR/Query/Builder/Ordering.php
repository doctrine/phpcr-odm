<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class Ordering extends OperandDynamicFactory
{
    protected $order;

    public function __construct(AbstractNode $parent, $order)
    {
        $this->order = $order;
        parent::__construct($parent);
    }

    public function getCardinalityMap()
    {
        return array(
            self::NT_OPERAND_DYNAMIC => array(1, 1),
        );
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getNodeType()
    {
        return self::NT_ORDERING;
    }
}
