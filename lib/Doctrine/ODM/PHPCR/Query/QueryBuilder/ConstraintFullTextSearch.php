<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;

class ConstraintFullTextSearch extends AbstractLeafNode implements 
    ConstraintInterface
{
    protected $selectorName;
    protected $propertyName;
    protected $fullTextSearchExpression;

    public function __construct(AbstractNode $parent, $propertyName, $fullTextSearchExpression, $selectorName)
    {
        $this->selectorName = $selectorName;
        $this->propertyName = $propertyName;
        $this->fullTextSearchExpression = $fullTextSearchExpression;
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
