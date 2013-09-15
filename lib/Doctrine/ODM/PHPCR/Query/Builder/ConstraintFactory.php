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
     * <code>
     *   $qb->where()
     *     ->orX()
     *       ->fieldExsts('prop_1', 'sel_1')
     *       ->fieldExsts('prop_2', 'sel_1')
     *     ->end()
     * </code>
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
     * @param string $field
     * @param string $alias
     *
     * <code>
     *   $qb->where()->fieldExists('prop_1', 'sel_1')
     * </code>
     *
     * @param string $field
     *
     * @factoryMethod
     * @return ConstraintFieldIsset
     */
    public function fieldIsset($field)
    {
        return $this->addChild(new ConstraintFieldIsset($this, $field));
    }

    /**
     * Full text search constraint:
     *
<<<<<<< HEAD
     *   $qb->where()->fullTextSearch('prop_1', 'search_expression', 'alias_1')
=======
     * <code>
     *   $qb->where()->fullTextSearch('sel_1.prop_1', 'search_expression')
     * </code>
>>>>>>> Working on dumping QB reference
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
<<<<<<< HEAD
     *   $qb->where()->sameDocument('/path/to/doc', 'alias_1')
=======
     * <code>
     *   $qb->where()->sameDocument('/path/to/doc', 'sel_1')
     * </code>
>>>>>>> Working on dumping QB reference
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
<<<<<<< HEAD
     *   $qb->where()->descendantDocument('/ancestor/path', 'alias_1')
=======
     * <code>
     *   $qb->where()->descendantDocument('/ancestor/path', 'sel_1')
     * </code>
>>>>>>> Working on dumping QB reference
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
<<<<<<< HEAD
     *   $qb->where()->child('/parent/path', 'alias_1')
=======
     * <code>
     *   $qb->where()->child('/parent/path', 'sel_1')
     * </code>
>>>>>>> Working on dumping QB reference
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
<<<<<<< HEAD
     *   $qb->where()->not()->fieldIsset('foobar', 'alias_1')
=======
     * <code>
     *   $qb->where()->not()->fieldExists('sel_1.foobar')
     * </code>
>>>>>>> Working on dumping QB reference
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
     * <code>
     *   $qb->where()
     *     ->eq()
<<<<<<< HEAD
     *       ->lop()->propertyValue('foobar', 'alias_1')->end()
     *       ->rop()->bindVariable('var_1')->end()
=======
     *       ->field('sel_1.foobar')->end()
     *       ->bindVariable('var_1')->end()
>>>>>>> Working on dumping QB reference
     *     ->end()
     * </code>
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
     * <code>
     *   $qb->where()
     *     ->neq()
<<<<<<< HEAD
     *       ->lop()->propertyValue('foobar', 'alias_1')->end()
     *       ->rop()->bindVariable('var_1')->end()
=======
     *       ->field('sel_1.foobar')->end()
     *       ->bindVariable('var_1')->end()
>>>>>>> Working on dumping QB reference
     *     ->end()
     * </code>
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
     * <code>
     *   $qb->where()
     *     ->lt()
<<<<<<< HEAD
     *       ->lop()->propertyValue('foobar', 'alias_1')->end()
     *       ->rop()->literal(5)->end()
=======
     *       ->field('sel_1.foobar')->end()
     *       ->literal(5)->end()
>>>>>>> Working on dumping QB reference
     *     ->end()
     * </code>
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
     * <code>
     *   $qb->where()
     *     ->lte()
<<<<<<< HEAD
     *       ->lop()->propertyValue('foobar', 'alias_1')->end()
     *       ->rop()->literal(5)->end()
=======
     *       ->field('sel_1.foobar')->end()
     *       ->literal(5)->end()
>>>>>>> Working on dumping QB reference
     *     ->end()
     * </code>
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
     * <code>
     *   $qb->where()
     *     ->gt()
<<<<<<< HEAD
     *       ->lop()->propertyValue('foobar', 'alias_1')->end()
     *       ->rop()->literal(5)->end()
=======
     *       ->field('sel_1.foobar')->end()
     *       ->literal(5)->end()
>>>>>>> Working on dumping QB reference
     *     ->end()
     * </code>
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
<<<<<<< HEAD
     *   $qb->where()
     *     ->gte()
     *       ->lop()->propertyValue('foobar', 'alias_1')->end()
     *       ->rop()->literal(5)->end()
     *     ->end()
=======
     * <code>
     * $qb->where()
     *   ->gte()
     *     ->field('sel_1.foobar')->end()
     *     ->literal(5)->end()
     *   ->end()
     * </code>
>>>>>>> Working on dumping QB reference
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
     * <code>
     *   $qb->where()
     *     ->lt()
<<<<<<< HEAD
     *       ->lop()->propertyValue('foobar', 'alias_1')->end()
     *       ->rop()->literal('foo%')->end()
=======
     *       ->field('sel_1.foobar')->end()
     *       ->literal('foo%')->end()
>>>>>>> Working on dumping QB reference
     *     ->end()
     * </code>
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
