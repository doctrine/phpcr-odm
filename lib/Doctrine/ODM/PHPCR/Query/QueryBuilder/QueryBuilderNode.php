<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

abstract class QueryBuilderNode
{
    protected $parent;
    protected $children;

    public function __construct(QueryBuilderNode $parent = null)
    {
        $this->parent = $parent;
    }

    public function end()
    {
        return $this->parent;
    }

    public function addChild(QueryBuilderNode $child)
    {
        $cardinalities = $this->getCardinalities();
        $this->children[] = $child;
    }

    public function getNext()
    {
    }
}

