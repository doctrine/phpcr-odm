<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class OperandDynamicFullTextSearchScore extends AbstractLeafNode implements OperandDynamicInterface
{
    protected $selectorName;

    public function __construct(AbstractNode $parent, $selectorName)
    {
        $this->selectorName = $selectorName;
        parent::__construct($parent);
    }

    public function getSelectorName() 
    {
        return $this->selectorName;
    }
}
