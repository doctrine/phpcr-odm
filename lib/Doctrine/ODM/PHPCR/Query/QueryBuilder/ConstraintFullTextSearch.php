<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;

class ConstraintFullTextSearch extends AbstractLeafNode
{
    protected $selectorName;
    protected $propertyName;
    protected $fullTextSearchExpression;

    public function __construct(AbstractNode $parent, $propertyName, $fullTextSearchExpression, $selectorName)
    {
        $this->selectorName = $selectorName;
        $this->propertyName = $propertyName;
        $this->fullTextSearchExpression = $fullTextSearchExpression;
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

    public function getPropertyName()
    {
        return $this->propertyName;
    }

    public function getFullTextSearchExpression()
    {
        return $this->fullTextSearchExpression;
    }
}
