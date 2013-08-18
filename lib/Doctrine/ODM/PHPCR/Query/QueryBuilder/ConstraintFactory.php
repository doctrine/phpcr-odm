<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

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
        return $this->addChild(new ConstraintAndx($this));
    }

    public function orX()
    {
        return $this->addChild(new ConstraintOrx($this));
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

    public function eq()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_EQUAL_TO
        ));
    }

    public function neq()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_NOT_EQUAL_TO
        ));
    }

    public function lt()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_LESS_THAN
        ));
    }

    public function lte()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO
        ));
    }

    public function gt()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_GREATER_THAN
        ));
    }

    public function gte()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO
        ));
    }

    public function like()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_LIKE
        ));
    }
}
