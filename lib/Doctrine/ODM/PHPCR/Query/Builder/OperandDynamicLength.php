<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class OperandDynamicLength extends AbstractLeafNode
{
    private string $alias;

    private string $field;

    public function __construct(AbstractNode $parent, string $field)
    {
        [$alias, $field] = $this->explodeField($field);
        $this->field = $field;
        $this->alias = $alias;
        parent::__construct($parent);
    }

    public function getNodeType(): string
    {
        return self::NT_OPERAND_DYNAMIC;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }
}
