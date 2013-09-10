<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;

class ConstraintSame extends AbstractLeafNode
{
    protected $selectorName;
    protected $path;

    public function __construct(AbstractNode $parent, $selectorName, $path)
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
