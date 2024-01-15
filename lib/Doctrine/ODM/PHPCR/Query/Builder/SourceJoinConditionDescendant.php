<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

class SourceJoinConditionDescendant extends AbstractLeafNode
{
    private string $descendantAlias;
    private string $ancestorAliasNode;

    public function __construct(AbstractNode $parent, string $descendantAlias, string $ancestorAlias)
    {
        $this->ancestorAliasNode = $ancestorAlias;
        $this->descendantAlias = $descendantAlias;
        parent::__construct($parent);
    }

    public function getNodeType(): string
    {
        return self::NT_SOURCE_JOIN_CONDITION;
    }

    public function getDescendantAlias(): string
    {
        return $this->descendantAlias;
    }

    public function getAncestorAlias(): string
    {
        return $this->ancestorAliasNode;
    }
}
