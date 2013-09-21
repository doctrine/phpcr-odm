<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;

class ConstraintSame extends AbstractLeafNode
{
    protected $alias;
    protected $path;

    public function __construct(AbstractNode $parent, $alias, $path)
    {
        $this->alias = $alias;
        $this->path = $path;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function getPath()
    {
        return $this->path;
    }
}
