<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;
use PHPCR\Query\QOM\DescendantNodeJoinConditionInterface;

class SourceJoinConditionDescendant extends AbstractLeafNode implements 
    DescendantNodeJoinConditionInterface
{
    protected $path;
    protected $descendantSelectorName;
    protected $ancestorSelectorNode;

    /**
     * Constructor
     *
     * @param string $descendantSelectorName
     * @param string $ancestorSelectorName
     */
    public function __construct($descendantSelectorName, $ancestorSelectorName)
    {
        $this->ancestorSelectorNode = (string) $ancestorSelectorName;
        $this->descendantSelectorName = (string) $descendantSelectorName;
    }

    public function getDescendantSelectorName()
    {
        return $this->descendantSelectorName;
    }

    public function getAncestorSelectorName()
    {
        return $this->ancestorSelectorNode;
    }
}

