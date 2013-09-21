<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class OperandDynamicLength extends AbstractLeafNode
{
    protected $alias;
    protected $field;

    Public function __construct(AbstractNode $parent, $field)
    {
        list($alias, $field) = $this->explodeField($field);
        $this->field = $field;
        $this->alias = $alias;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_OPERAND_DYNAMIC;
    }

    public function getField() 
    {
        return $this->field;
    }

    public function getAlias() 
    {
        return $this->alias;
    }
}
