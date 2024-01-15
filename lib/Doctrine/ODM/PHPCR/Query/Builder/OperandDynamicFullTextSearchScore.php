<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class OperandDynamicFullTextSearchScore extends AbstractLeafNode
{
    private string $alias;

    public function __construct(AbstractNode $parent, string $alias)
    {
        $this->alias = $alias;
        parent::__construct($parent);
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getNodeType(): string
    {
        return self::NT_OPERAND_DYNAMIC;
    }
}
