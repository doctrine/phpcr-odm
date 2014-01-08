<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;
use PHPCR\Query\QOM\DescendantNodeJoinConditionInterface;

class SourceJoinConditionDescendant extends AbstractLeafNode
{
    protected $path;
    protected $descendantAlias;
    protected $ancestorAliasNode;

    /**
     * Constructor
     *
     * @param string $descendantAlias
     * @param string $ancestorAlias
     */
    public function __construct($parent, $descendantAlias, $ancestorAlias)
    {
        $this->ancestorAliasNode = (string) $ancestorAlias;
        $this->descendantAlias = (string) $descendantAlias;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_SOURCE_JOIN_CONDITION;
    }

    public function getDescendantAlias()
    {
        return $this->descendantAlias;
    }

    public function getAncestorAlias()
    {
        return $this->ancestorAliasNode;
    }
}
