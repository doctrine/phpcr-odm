<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Operand evaluates to the value of the given field of the
 * aliased document.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class OperandDynamicField extends AbstractLeafNode
{
    protected $alias;
    protected $field;

    public function __construct(AbstractNode $parent, $field)
    {
        list($alias, $field) = $this->explodeField($field);
        $this->alias = $alias;
        $this->field = $field;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_OPERAND_DYNAMIC;
    }

    public function getAlias() 
    {
        return $this->alias;
    }

    public function getField() 
    {
        return $this->field;
    }
}
