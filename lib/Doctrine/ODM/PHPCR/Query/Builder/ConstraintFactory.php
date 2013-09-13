<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

/**
 * @IgnoreAnnotation("factoryMethod")
 */
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
     * @factoryMethod
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
     * @factoryMethod
     * @return ConstraintOrx
     */
    public function orX()
    {
        return $this->addChild(new ConstraintOrx($this));
    }

    /**
     * Field existance constraint:
     *
     *   $qb->where()->propertyExists('prop_1', 'sel_1')
     *
     * @param string $propertyName
     * @param string $selectorName
     *
     * @factoryMethod
     * @return ConstraintFieldExists
     */
    public function fieldExists($field)
    {
        return $this->addChild(new ConstraintFieldExists($this, $field));
    }

    /**
     * Full text search constraint:
     *
     *   $qb->where()->fullTextSearch('prop_1', 'search_expression', 'sel_1')
     *
     * @param string $field
     * @param string $fullTextSearchExpression
     *
     * @factoryMethod
     * @return ConstraintFullTextSearch
     */
    public function fullTextSearch($field, $fullTextSearchExpression)
    {
        return $this->addChild(new ConstraintFullTextSearch($this, $field, $fullTextSearchExpression));
    }

    /**
     * Same document constraint:
     *
     *   $qb->where()->sameDocument('/path/to/doc', 'sel_1')
     *
     * Relates to PHPCR SameNodeInterface
     *
     * @param string $path
     * @param string $selectorName
     *
     * @factoryMethod
     * @return ConstraintSame
     */
    public function same($path, $selectorName)
    {
        return $this->addChild(new ConstraintSame($this, $selectorName, $path));
    }

    /**
     * Descendant document constraint:
     *
     *   $qb->where()->descendantDocument('/ancestor/path', 'sel_1')
     *
     * Relates to PHPCR DescendantNodeInterface
     *
     * @param string $ancestorPath
     * @param string $selectorName
     *
     * @factoryMethod
     * @return ConstraintDescendant
     */
    public function descendant($ancestorPath, $selectorName)
    {
        return $this->addChild(new ConstraintDescendant($this, $selectorName, $ancestorPath));
    }

    /**
     * Child document constraint:
     *
     *   $qb->where()->child('/parent/path', 'sel_1')
     *
     * @param string $parentPath
     * @param string $selectorName
     *
     * Relates to PHPCR ChildNodeInterface
     *
     * @factoryMethod
     * @return ConstraintChild
     */
    public function child($parentPath, $selectorName)
    {
        return $this->addChild(new ConstraintChild($this, $selectorName, $parentPath));
    }

    /**
     * Not constraint.
     *
     * Inverts the truth of any given constraint:
     *
     *   $qb->where()->not()->propertyExists('foobar', 'sel_1')
     *
     * @factoryMethod
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
     * @factoryMethod
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
     * @factoryMethod
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
     * @factoryMethod
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
     * @factoryMethod
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
     * @factoryMethod
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
     * @factoryMethod
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
     * @factoryMethod
     * @return ConstraintComparison
     */
    public function like()
    {
        return $this->addChild(new ConstraintComparison(
            $this, QOMConstants::JCR_OPERATOR_LIKE
        ));
    }
}
