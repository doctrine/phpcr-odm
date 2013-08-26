<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class ConstraintFactory extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            self::NT_CONSTRAINT => array(1, 1),
        );
    }

    public function getNodeType()
    {
        return self::NT_CONSTRAINT_FACTORY;
    }

    /**
     * And composite constraint, usage
     *
     *   $qb->where()
     *     ->andX()
     *       ->propertyExsts('prop_1', 'sel_1')
     *       ->propertyExsts('prop_2', 'sel_1')
     *     ->end()
     *
     * @return ConstraintAndx
     */
    public function andX()
    {
        return $this->addChild(new ConstraintAndx($this));
    }


    /**
     * Or composite constraint:
     *
     *   $qb->where()
     *     ->orX()
     *       ->propertyExsts('prop_1', 'sel_1')
     *       ->propertyExsts('prop_2', 'sel_1')
     *     ->end()
     *
     * @return ConstraintOrx
     */
    public function orX()
    {
        return $this->addChild(new ConstraintOrx($this));
    }

    /**
     * Property existance constraint:
     *
     *   $qb->where()->propertyExists('prop_1', 'sel_1')
     *
     * @param string $propertyName
     * @param string $selectorName
     *
     * @return ConstraintPropertyExists
     */
    public function propertyExists($propertyName, $selectorName)
    {
        return $this->addChild(new ConstraintPropertyExists($this, $propertyName, $selectorName));
    }

    /**
     * Full text search constraint:
     *
     *   $qb->where()->fullTextSearch('prop_1', 'search_expression', 'sel_1')
     *
     * @param string $propertyName
     * @param string $fullTextSearchExpression
     * @param string $selectorName
     *
     * @return ConstraintFullTextSearch
     */
    public function fullTextSearch($propertyName, $fullTextSearchExpression, $selectorName)
    {
        return $this->addChild(new ConstraintFullTextSearch($this, $propertyName, $fullTextSearchExpression, $selectorName));
    }

    /**
     * Same document constraint:
     *
     *   $qb->where()->sameDocument('/path/to/doc', 'sel_1')
     *
     * @param string $path
     * @param string $selectorName
     *
     * @return ConstraintSameDocument
     */
    public function sameDocument($path, $selectorName)
    {
        return $this->addChild(new ConstraintSameDocument($this, $path, $selectorName));
    }

    /**
     * Descendant document constraint:
     *
     *   $qb->where()->descendantDocument('/ancestor/path', 'sel_1')
     *
     * @param string $ancestorPath
     * @param string $selectorName
     *
     * @return ConstraintDescendantDocument
     */
    public function descendantDocument($ancestorPath, $selectorName)
    {
        return $this->addChild(new ConstraintDescendantDocument($this, $ancestorPath, $selectorName));
    }

    /**
     * Child document constraint:
     *
     *   $qb->where()->childDocument('/parent/path', 'sel_1')
     *
     * @param string $parentPath
     * @param string $selectorName
     *
     * @return ConstraintChildDocument
     */
    public function childDocument($parentPath, $selectorName)
    {
        return $this->addChild(new ConstraintChildDocument($this, $parentPath, $selectorName));
    }

    /**
     * Not constraint.
     *
     * Inverts the truth of any given constraint:
     *
     *   $qb->where()->not()->propertyExists('foobar', 'sel_1')
     *
     * @return ConstraintNot
     */
    public function not()
    {
        return $this->addChild(new ConstraintNot($this));
    }

    /**
     * Equality comparison constraint
     *
     *   $qb->where()
     *     ->eq()
     *       ->lop()->propertyValue('foobar', 'sel_1')->end()
     *       ->rop()->bindVariable('var_1')->end()
     *     ->end()
     *
     * @return ConstraintComparison
     */
    public function eq()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_EQUAL_TO
        ));
    }

    /**
     * Inequality comparison constraint
     *
     *   $qb->where()
     *     ->neq()
     *       ->lop()->propertyValue('foobar', 'sel_1')->end()
     *       ->rop()->bindVariable('var_1')->end()
     *     ->end()
     *
     * @return ConstraintComparison
     */
    public function neq()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_NOT_EQUAL_TO
        ));
    }

    /**
     * Less than comparison constraint
     *
     *   $qb->where()
     *     ->lt()
     *       ->lop()->propertyValue('foobar', 'sel_1')->end()
     *       ->rop()->literal(5)->end()
     *     ->end()
     *
     * @return ConstraintComparison
     */
    public function lt()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_LESS_THAN
        ));
    }

    /**
     * Less than or equal to comparison constraint
     *
     *   $qb->where()
     *     ->lte()
     *       ->lop()->propertyValue('foobar', 'sel_1')->end()
     *       ->rop()->literal(5)->end()
     *     ->end()
     *
     * @return ConstraintComparison
     */
    public function lte()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO
        ));
    }

    /**
     * Greater than comparison constraint
     *
     *   $qb->where()
     *     ->gt()
     *       ->lop()->propertyValue('foobar', 'sel_1')->end()
     *       ->rop()->literal(5)->end()
     *     ->end()
     *
     * @return ConstraintComparison
     */
    public function gt()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_GREATER_THAN
        ));
    }

    /**
     * Greater than or equal to comparison constraint
     *
     *   $qb->where()
     *     ->gte()
     *       ->lop()->propertyValue('foobar', 'sel_1')->end()
     *       ->rop()->literal(5)->end()
     *     ->end()
     *
     * @return ConstraintComparison
     */
    public function gte()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO
        ));
    }

    /**
     * Like comparison constraint
     *
     *   $qb->where()
     *     ->lt()
     *       ->lop()->propertyValue('foobar', 'sel_1')->end()
     *       ->rop()->literal('foo%')->end()
     *     ->end()
     *
     * @return ConstraintComparison
     */
    public function like()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_LIKE
        ));
    }
}
