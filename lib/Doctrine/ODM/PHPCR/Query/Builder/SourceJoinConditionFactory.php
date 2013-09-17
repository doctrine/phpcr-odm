<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;

/**
 * Factory/node class for join conditions.
 *
 * @IgnoreAnnotation('factoryMethod');
 * @author Daniel Leech <daniel@dantleech.com>
 */
class SourceJoinConditionFactory extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            self::NT_SOURCE_JOIN_CONDITION => array(1, 1)
        );
    }

    public function getNodeType()
    {
        return self::NT_SOURCE_JOIN_CONDITION_FACTORY;
    }

    /**
     * Descendant join condition.
     *
     * <code>
     *   $qb->from()
     *     ->joinInner()
     *       ->left()->document('Foo/Bar/One', 'alias_1')->end()
     *       ->right()->document('Foo/Bar/Two', 'alias_2')->end()
     *       ->condition()
     *         ->descendant('alias_1', 'alias_2')
     *       ->end()
     *   ->end()
     * </code>
     *
     * @param string $descendantSelectorName - Name of selector for descendant documents.
     * @param string $ancestorSelectorName - Name of selector to match for ancestor documents.
     *
     * @factoryMethod
     * @return SourceJoinConditionFactory
     */
    public function descendant($descendantAlias, $ancestorAlias)
    {
        return $this->addChild(new SourceJoinConditionDescendant($this, 
            $descendantAlias, $ancestorAlias
        ));
    }

    /**
     * Equi (equality) join condition.
     *
     * <code>
     *   $qb->from()
     *     ->joinInner()
     *       ->left()->document('Foo/Bar/One', 'alias_1')->end()
     *       ->right()->document('Foo/Bar/Two', 'alias_2')->end()
     *       ->condition()->equi('alias_1.prop_1', 'alias_2.prop_2');
     * </code>
     *
     * @param string $field1 - Field name for first field.
     * @param string $field2 - Field name for second field.
     *
     * @factoryMethod
     * @return SourceJoinConditionFactory
     */
    public function equi($field1, $field2)
    {
        return $this->addChild(new SourceJoinConditionEqui($this,
            $field1, $field2
        ));
    }

    /**
     * Child document join condition.
     *
     * <code>
     *   $qb->from()
     *     ->joinInner()
     *       ->left()->document('Foo/Bar/One', 'alias_1')->end()
     *       ->right()->document('Foo/Bar/Two', 'alias_2')->end()
     *       ->condition()->child('alias_1', 'alias_2');
     * </code>
     *
     * @param string $childSelectorName - Name of selector for child documents.
     * @param string $parentSelectorName - Name of selector to match for parent documents.
     *
     * @factoryMethod
     * @return SourceJoinConditionFactory
     */
    public function child($childAlias, $parentAlias)
    {
        return $this->addChild(new SourceJoinConditionChildDocument($this, 
            $childAlias, $parentAlias
        ));
    }

    /**
     * Same document join condition:
     *
     * <code>
     *   $qb->from()
     *     ->joinInner()
     *       ->left()->document('Foo/Bar/One', 'alias_1')->end()
     *       ->right()->document('Foo/Bar/Two', 'alias_2')->end()
     *       ->condition()
     *         ->same('alias_1', 'alias_2', '/path_to/alias_2/document')
     *       ->end()
     *     ->end()
     * </code>
     *
     * @param string $selector1Name - Name of first selector.
     * @param string $selector2Name - Name of first selector.
     * @param string $selector2Path - Path for documents of second selector.
     *
     * @factoryMethod
     * @return SourceJoinConditionFactory
     */
    public function same($selector1Name, $selector2Name, $selector2Path)
    {
        return $this->addChild(new SourceJoinConditionSameDocument($this, 
            $selector1Name, $selector2Name, $selector2Path
        ));
    }
}
