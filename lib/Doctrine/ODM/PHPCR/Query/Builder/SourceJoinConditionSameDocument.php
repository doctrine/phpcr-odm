<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class SourceJoinConditionSameDocument extends AbstractLeafNode
{
    private string $alias1Name;

    private string $alias2Name;

    private string $alias2Path;

    public function __construct(AbstractNode $parent, string $alias1Name, string $alias2Name, string $alias2Path)
    {
        $this->alias1Name = $alias1Name;
        $this->alias2Name = $alias2Name;
        $this->alias2Path = $alias2Path;
        parent::__construct($parent);
    }

    public function getNodeType(): string
    {
        return self::NT_SOURCE_JOIN_CONDITION;
    }

    public function getAlias1Name(): string
    {
        return $this->alias1Name;
    }

    public function getAlias2Name(): string
    {
        return $this->alias2Name;
    }

    public function getAlias2Path(): string
    {
        return $this->alias2Path;
    }
}
