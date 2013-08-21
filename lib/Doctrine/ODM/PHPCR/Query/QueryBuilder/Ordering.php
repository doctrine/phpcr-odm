<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class Ordering extends OperandDynamicFactory
{
    protected $order;

    public function __construct(AbstractNode $parent, $order)
    {
        $this->order = $order;
        parent::__construct($parent);
    }

    public function getOrder()
    {
        return $this->order;
    }
}
