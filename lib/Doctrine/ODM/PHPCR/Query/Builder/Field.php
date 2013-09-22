<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class Field extends AbstractLeafNode
{
    protected $field;
    protected $alias;

    public function __construct(AbstractNode $parent, $field)
    {
        list($alias, $field) = $this->explodeField($field);
        $this->field = $field;
        $this->alias = $alias;
        parent::__construct($parent);
    }

    public function getField() 
    {
        return $this->field;
    }

    public function getAlias() 
    {
        return $this->alias;
    }

    public function getNodeType()
    {
        return self::NT_PROPERTY;
    }
}
