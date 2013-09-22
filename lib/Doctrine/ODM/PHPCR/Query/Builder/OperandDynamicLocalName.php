<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class OperandDynamicLocalName extends AbstractLeafNode
{
    protected $alias;

    public function __construct(AbstractNode $parent, $alias)
    {
        $this->alias = $alias;
        parent::__construct($parent);
    }

    public function getAlias() 
    {
        return $this->alias;
    }

    public function getNodeType()
    {
        return self::NT_OPERAND_DYNAMIC;
    }
}
