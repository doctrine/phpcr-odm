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
     *       ->propertyExsts('prop_1', 'alias_1')
     *       ->propertyExsts('prop_2', 'alias_1')
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
     *       ->propertyExsts('prop_1', 'alias_1')
     *       ->propertyExsts('prop_2', 'alias_1')
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
     *   $qb->where()->propertyExists('prop_1', 'alias_1')
     *
     * @param string $field
     * @param string $alias
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
     *   $qb->where()->fullTextSearch('prop_1', 'search_expression', 'alias_1')
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
     *   $qb->where()->sameDocument('/path/to/doc', 'alias_1')
     *
     * Relates to PHPCR SameNodeInterface
     *
     * @param string $path
     * @param string $alias
     *
     * @factoryMethod
     * @return ConstraintSame
     */
    public function same($path, $alias)
    {
        return $this->addChild(new ConstraintSame($this, $alias, $path));
    }

    /**
     * Descendant document constraint:
     *
     *   $qb->where()->descendantDocument('/ancestor/path', 'alias_1')
     *
     * Relates to PHPCR DescendantNodeInterface
     *
     * @param string $ancestorPath
     * @param string $alias
     *
     * @factoryMethod
     * @return ConstraintDescendant
     */
    public function descendant($ancestorPath, $alias)
    {
        return $this->addChild(new ConstraintDescendant($this, $alias, $ancestorPath));
    }

    /**
     * Child document constraint:
     *
     *   $qb->where()->child('/parent/path', 'alias_1')
     *
     * @param string $parentPath
     * @param string $alias
     *
     * Relates to PHPCR ChildNodeInterface
     *
     * @factoryMethod
     * @return ConstraintChild
     */
    public function child($parentPath, $alias)
    {
        return $this->addChild(new ConstraintChild($this, $alias, $parentPath));
    }

    /**
     * Not constraint.
     *
     * Inverts the truth of any given constraint:
     *
     *   $qb->where()->not()->propertyExists('foobar', 'alias_1')
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
     *       ->lop()->propertyValue('foobar', 'alias_1')->end()
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
     *       ->lop()->propertyValue('foobar', 'alias_1')->end()
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
     *       ->lop()->propertyValue('foobar', 'alias_1')->end()
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
     *       ->lop()->propertyValue('foobar', 'alias_1')->end()
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
     *       ->lop()->propertyValue('foobar', 'alias_1')->end()
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
     *       ->lop()->propertyValue('foobar', 'alias_1')->end()
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
     *       ->lop()->propertyValue('foobar', 'alias_1')->end()
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
