<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;

class ConstraintFullTextSearch extends AbstractLeafNode
{
    protected $selectorName;
    protected $propertyName;
    protected $fullTextSearchExpression;

    Public function __construct(AbstractNode $parent, $field, $fullTextSearchExpression)
    {
        list($selectorName, $propertyName) = $this->explodeField($field);
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
