<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class OperandDynamicFullTextSearchScore extends AbstractLeafNode
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

    public function getNodeType()
    {
        return self::NT_OPERAND_DYNAMIC;
    }
}
