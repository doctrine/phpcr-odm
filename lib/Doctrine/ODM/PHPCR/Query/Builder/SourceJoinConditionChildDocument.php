<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class SourceJoinConditionChildDocument extends AbstractLeafNode
{
    private string $childAlias;

    private string $parentAlias;

    public function __construct(AbstractNode $parent, string $childAlias, string $parentAlias)
    {
        $this->childAlias = $childAlias;
        $this->parentAlias = $parentAlias;
        parent::__construct($parent);
    }

    public function getNodeType(): string
    {
        return self::NT_SOURCE_JOIN_CONDITION;
    }

    public function getChildAlias(): string
    {
        return $this->childAlias;
    }

    public function getParentAlias(): string
    {
        return $this->parentAlias;
    }
}
