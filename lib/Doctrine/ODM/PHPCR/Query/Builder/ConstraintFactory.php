<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

/**
 * This factory node provides both leaf and factory nodes all of which
 * return nodes of type "constraint".
 *
 * @IgnoreAnnotation("factoryMethod")
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class ConstraintFactory extends AbstractNode
{
    public function getCardinalityMap(): array
    {
        return [
            self::NT_CONSTRAINT => [1, 1],
        ];
    }

    public function getNodeType(): string
    {
        return self::NT_CONSTRAINT_FACTORY;
    }

    /**
     * And composite constraint::.
     *
     * <code>
     * $qb->where()
     *   ->andX()
     *     ->fieldIsset('f.foo')
     *     ->gt()->field('f.max')->literal(40)->end()
     *   ->end()
     * ->end();
     * </code>
     *
     * The andX node allows you to add 1, 2 or many operand nodes. When
     * one operand is added the "and" is removed, when more than one
     * is added the "and" operands are nested::
     *
     * <code>
     * // when adding only a single operand,
     * $qb->where()->andX()->eq()->field('f.foo')->literal('bar');
     * // is equivilent to:
     * $qb->where()->eq()->field('f.foo')->literal('bar');
     *
     *
     * // when adding more than one,
     * $qb->where()
     *   ->andX()
     *     ->fieldIsset('f.foo')
     *     ->gt()->field('f.max')->literal(40)->end()
     *     ->eq()->field('f.zar')->literal('bar')->end()
     *   ->end()
     * ->end();
     *
     * // is equivilent to:
     * $qb->where()
     *   ->andX()
     *     ->fieldIsset('f.foo')
     *     ->andX()
     *       ->gt()->field('f.max')->literal(40)->end()
     *       ->eq()->field('f.zar')->litreal('bar')->end()
     *     ->end()
     *   ->end()
     * ->end();
     * </code>
     *
     * @factoryMethod ConstraintAndx
     */
    public function andX(): ConstraintAndx
    {
        return $this->addChild(new ConstraintAndx($this));
    }

    /**
     * Or composite constraint::.
     *
     * <code>
     * $qb->where()
     *   ->orX()
     *     ->fieldIsset('sel_1.prop_1')
     *     ->fieldIsset('sel_1.prop_2')
     *   ->end()
     * ->end();
     * </code>
     *
     * As with "andX", "orX" allows one to many operands.
     *
     * @factoryMethod ConstraintOrx
     */
    public function orX(): ConstraintOrx
    {
        return $this->addChild(new ConstraintOrx($this));
    }

    /**
     * Field existance constraint::.
     *
     * <code>
     * $qb->where()->fieldIsset('sel_1.prop_1')->end();
     * </code>
     *
     * @param string $field - Field to check
     *
     * @factoryMethod ConstraintFieldIsset
     */
    public function fieldIsset(string $field): ConstraintFactory
    {
        return $this->addChild(new ConstraintFieldIsset($this, $field));
    }

    /**
     * Full text search constraint::.
     *
     * <code>
     * $qb->where()->fullTextSearch('sel_1.prop_1', 'search_expression')->end();
     * </code>
     *
     * @param string $field                    - Name of field to check, including alias name
     * @param string $fullTextSearchExpression - Search expression
     *
     * @factoryMethod ConstraintFullTextSearch
     */
    public function fullTextSearch(string $field, string $fullTextSearchExpression): ConstraintFactory
    {
        return $this->addChild(new ConstraintFullTextSearch($this, $field, $fullTextSearchExpression));
    }

    /**
     * Same document constraint::.
     *
     * <code>
     * $qb->where()->same('/path/to/doc', 'sel_1')->end();
     * </code>
     *
     * Relates to PHPCR QOM SameNodeInterface.
     *
     * @param string $path  - Path to reference document
     * @param string $alias - Name of alias to use
     *
     * @factoryMethod ConstraintSame
     */
    public function same(string $path, string $alias): ConstraintFactory
    {
        return $this->addChild(new ConstraintSame($this, $alias, $path));
    }

    /**
     * Descendant document constraint::.
     *
     * <code>
     *   $qb->where()->descendant('/ancestor/path', 'sel_1')->end();
     * </code>
     *
     * Relates to PHPCR QOM DescendantNodeInterface
     *
     * @param string $ancestorPath - Select descendants of this path
     * @param string $alias        - Name of alias to use
     *
     * @factoryMethod ConstraintDescendant
     */
    public function descendant(string $ancestorPath, string $alias): ConstraintFactory
    {
        return $this->addChild(new ConstraintDescendant($this, $alias, $ancestorPath));
    }

    /**
     * Select children of the aliased document at the given path::.
     *
     * <code>
     * $qb->where()->child('/parent/path', 'sel_1')->end();
     * </code>
     *
     * Relates to PHPCR QOM ChildNodeInterface.
     *
     * @param string $parentPath - Select children of this path
     * @param string $alias      - Name of alias to use
     *
     * @factoryMethod ConstraintChild
     */
    public function child(string $parentPath, string $alias): ConstraintFactory
    {
        return $this->addChild(new ConstraintChild($this, $alias, $parentPath));
    }

    /**
     * Inverts the truth of the given appended constraint.
     *
     * @factoryMethod ConstraintNot
     */
    public function not(): ConstraintFactory
    {
        return $this->addChild(new ConstraintNot($this));
    }

    /**
     * Equality comparison constraint::.
     *
     * <code>
     * $qb->where()
     *   ->eq()
     *     ->field('sel_1.foobar')
     *     ->literal('var_1')
     *   ->end();
     * </code>
     *
     * @factoryMethod ConstraintComparison
     */
    public function eq(): ConstraintComparison
    {
        return $this->addChild(new ConstraintComparison(
            $this,
            QOMConstants::JCR_OPERATOR_EQUAL_TO
        ));
    }

    /**
     * Inequality comparison constraint::.
     *
     * <code>
     * $qb->where()
     *   ->neq()
     *     ->field('sel_1.foobar')
     *     ->literal('var_1')
     *   ->end();
     * </code>
     *
     * @factoryMethod ConstraintComparison
     */
    public function neq(): ConstraintComparison
    {
        return $this->addChild(new ConstraintComparison(
            $this,
            QOMConstants::JCR_OPERATOR_NOT_EQUAL_TO
        ));
    }

    /**
     * Less than comparison constraint::.
     *
     * <code>
     * $qb->where()
     *   ->lt()
     *     ->field('sel_1.foobar')
     *     ->literal(5)
     *   ->end();
     * </code>
     *
     * @factoryMethod ConstraintComparison
     */
    public function lt(): ConstraintComparison
    {
        return $this->addChild(new ConstraintComparison(
            $this,
            QOMConstants::JCR_OPERATOR_LESS_THAN
        ));
    }

    /**
     * Less than or equal to comparison constraint::.
     *
     * <code>
     * $qb->where()
     *   ->lte()
     *     ->field('sel_1.foobar')
     *     ->literal(5)
     *   ->end();
     * </code>
     *
     * @factoryMethod ConstraintComparison
     */
    public function lte(): ConstraintComparison
    {
        return $this->addChild(new ConstraintComparison(
            $this,
            QOMConstants::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO
        ));
    }

    /**
     * Greater than comparison constraint::.
     *
     * <code>
     * $qb->where()
     *   ->gt()
     *     ->field('sel_1.foobar')
     *     ->literal(5)
     *   ->end();
     * </code>
     *
     * @factoryMethod ConstraintComparison
     */
    public function gt(): ConstraintComparison
    {
        return $this->addChild(new ConstraintComparison(
            $this,
            QOMConstants::JCR_OPERATOR_GREATER_THAN
        ));
    }

    /**
     * Greater than or equal to comparison constraint::.
     *
     * <code>
     * $qb->where()
     *   ->gte()
     *     ->field('sel_1.foobar')
     *     ->literal(5)
     *   ->end();
     * </code>
     *
     * @factoryMethod ConstraintComparison
     */
    public function gte(): ConstraintComparison
    {
        return $this->addChild(new ConstraintComparison(
            $this,
            QOMConstants::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO
        ));
    }

    /**
     * Like comparison constraint.
     *
     * Use "%" as wildcards::
     *
     * <code>
     * $qb->where()
     *   ->like()
     *     ->field('sel_1.foobar')
     *     ->literal('foo%')
     *   ->end();
     * </code>
     *
     * The above example will match "foo" and "foobar" but not "barfoo".
     *
     * @factoryMethod ConstraintComparison
     */
    public function like(): ConstraintComparison
    {
        return $this->addChild(new ConstraintComparison(
            $this,
            QOMConstants::JCR_OPERATOR_LIKE
        ));
    }
}
