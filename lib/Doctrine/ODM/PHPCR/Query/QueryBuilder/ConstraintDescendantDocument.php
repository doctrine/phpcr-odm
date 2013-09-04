<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class ConstraintDescendantDocument extends AbstractLeafNode
{
    protected $selectorName;
    protected $ancestorPath;

    public function __construct(AbstractNode $parent, $selectorName, $ancestorPath)
    {
        $this->selectorName = $selectorName;
        $this->ancestorPath = $ancestorPath;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

    public function getAncestorPath() 
    {
        return $this->ancestorPath;
    }

    public function getSelectorName() 
    {
        return $this->selectorName;
    }
}
