<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;

/**
 * Factory node for join conditions.
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
     *   $qb->from('alias_1')
     *     ->joinInner()
     *       ->left()->document('Foo/Bar/One', 'alias_1')->end()
     *       ->right()->document('Foo/Bar/Two', 'alias_2')->end()
     *       ->condition()
     *         ->descendant('alias_1', 'alias_2')
     *       ->end()
     *   ->end()
     * </code>
     *
     * @param string $descendantAlias - Name of alias for descendant documents.
     * @param string $ancestorAlias - Name of alias to match for ancestor documents.
     *
     * @factoryMethod SourceJoinConditionDescendant
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
     *   $qb->from('alias_1')
     *     ->joinInner()
     *       ->left()->document('Foo/Bar/One', 'alias_1')->end()
     *       ->right()->document('Foo/Bar/Two', 'alias_2')->end()
     *       ->condition()->equi('alias_1.prop_1', 'alias_2.prop_2');
     * </code>
     *
     * See: http://en.wikipedia.org/wiki/Join_%28SQL%29#Equi-join
     *
     * @param string $field1 - Field name for first field.
     * @param string $field2 - Field name for second field.
     *
     * @factoryMethod SourceJoinConditionEqui
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
     *   $qb->from('alias_1')
     *     ->joinInner()
     *       ->left()->document('Foo/Bar/One', 'alias_1')->end()
     *       ->right()->document('Foo/Bar/Two', 'alias_2')->end()
     *       ->condition()->child('alias_1', 'alias_2');
     * </code>
     *
     * @param string $childAlias - Name of alias for child documents.
     * @param string $parentAlias - Name of alias to match for parent documents.
     *
     * @factoryMethod SourceJoinConditionChildDocument
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
     *   $qb->from('alias_1')
     *     ->joinInner()
     *       ->left()->document('Foo/Bar/One', 'alias_1')->end()
     *       ->right()->document('Foo/Bar/Two', 'alias_2')->end()
     *       ->condition()
     *         ->same('alias_1', 'alias_2', '/path_to/alias_2/document')
     *       ->end()
     *     ->end()
     * </code>
     *
     * @param string $alias1 - Name of first alias.
     * @param string $alias2 - Name of first alias.
     * @param string $alias2Path - Path for documents of second alias.
     *
     * @factoryMethod SourceJoinConditionSameDocument
     * @return SourceJoinConditionFactory
     */
    public function same($alias1, $alias2, $alias2Path)
    {
        return $this->addChild(new SourceJoinConditionSameDocument($this, 
            $alias1, $alias2, $alias2Path
        ));
    }
}
