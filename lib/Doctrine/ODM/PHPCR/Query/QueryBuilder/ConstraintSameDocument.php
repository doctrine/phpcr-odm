<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;

class ConstraintSameDocument extends AbstractLeafNode implements 
    ConstraintInterface
{
    protected $selectorName;
    protected $path;

    public function __construct(AbstractNode $parent, $path, $selectorName)
    {
        $this->selectorName = $selectorName;
        $this->path = $path;
        parent::__construct($parent);
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
