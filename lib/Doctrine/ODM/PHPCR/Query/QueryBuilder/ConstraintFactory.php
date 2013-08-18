<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class ConstraintFactory extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            'ConstraintInterface' => array(1, 1),
        );
    }

    public function andX()
    {
        return $this->addChild(new ConstraintAndx($this, $constraint1, $constraint2));
    }

    public function orX()
    {
        return $this->addChild(new ConstraintOrx($this, $constraint1, $constraint2));
    }

    public function propertyExists($propertyName, $selectorName)
    {
        return $this->addChild(new ConstraintPropertyExists($this, $propertyName, $selectorName));
    }

    public function fullTextSearch($propertyName, $fullTextSearchExpression, $selectorName)
    {
        return $this->addChild(new ConstraintFullTextSearch($this, $propertyName, $fullTextSearchExpression, $selectorName));
    }


    public function sameDocument($path, $selectorName)
    {
        return $this->addChild(new ConstraintSameDocument($this, $path, $selectorName));
    }

    public function descendantDocument($ancestorPath, $selectorName)
    {
        return $this->addChild(new ConstraintDescendantDocument($this, $ancestorPath, $selectorName));
    }

    public function childDocument($parentPath, $selectorName)
    {
        return $this->addChild(new ConstraintChildDocument($this, $parentPath, $selectorName));
    }

    public function not()
    {
        return $this->addChild(new ConstraintNot($this));
    }
}
