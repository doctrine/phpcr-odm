<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;

class ConstraintSameDocument extends AbstractLeafNode
{
    protected $selectorName;
    protected $path;

    public function __construct(AbstractNode $parent, $path, $selectorName)
    {
        $this->selectorName = $selectorName;
        $this->path = $path;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

    public function getSelectorName()
    {
        return $this->selectorName;
    }

    public function getPath()
    {
        return $this->path;
    }
}
