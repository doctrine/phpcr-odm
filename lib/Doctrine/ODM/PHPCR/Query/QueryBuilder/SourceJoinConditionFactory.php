<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;

/**
 * Factory/node class for join conditions.
 *
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
     * Descendant join condition:
     *
     *   $qb->from()
     *     ->joinInner()
     *       ->left()->document('Foo/Bar/One', 'sel_1')->end()
     *       ->right()->document('Foo/Bar/Two', 'sel_2')->end()
     *       ->condition()
     *         ->descendant('sel_1', 'sel_2')
     *       ->end()
     *     ->end()
     *
     * @return SourceJoinConditionDescendant
     */
    public function descendant($descendantSelectorName, $ancestorSelectorName)
    {
        return $this->addChild(new SourceJoinConditionDescendant($this, 
            $descendantSelectorName, $ancestorSelectorName
        ));
    }

    /**
     * Equi (equality) join condition:
     *
     *   $qb->from()
     *     ->joinInner()
     *       ->left()->document('Foo/Bar/One', 'sel_1')->end()
     *       ->right()->document('Foo/Bar/Two', 'sel_2')->end()
     *       ->condition()
     *         ->equi('prop_1', 'sel_1', 'prop_2', 'sel_2')
     *       ->end()
     *     ->end()
     *
     * @return SourceJoinConditionDescendant
     */
    public function equi($property1, $selector1Name, $property2, $selector2Name)
    {
        return $this->addChild(new SourceJoinConditionEqui($this,
            $property1, $selector1Name, $property2, $selector2Name
        ));
    }

    /**
     * Child document join condition:
     *
     *   $qb->from()
     *     ->joinInner()
     *       ->left()->document('Foo/Bar/One', 'sel_1')->end()
     *       ->right()->document('Foo/Bar/Two', 'sel_2')->end()
     *       ->condition()
     *         ->childDocument('sel_1', 'sel_2')
     *       ->end()
     *     ->end()
     *
     * @return SourceJoinConditionDescendant
     */
    public function childDocument($childSelectorName, $parentSelectorName)
    {
        return $this->addChild(new SourceJoinConditionChildDocument($this, 
            $childSelectorName, $parentSelectorName
        ));
    }

    /**
     * Same document join condition:
     *
     *   $qb->from()
     *     ->joinInner()
     *       ->left()->document('Foo/Bar/One', 'sel_1')->end()
     *       ->right()->document('Foo/Bar/Two', 'sel_2')->end()
     *       ->condition()
     *         ->sameDocument('sel_1', 'sel_2', '/path_to/sel_2/document')
     *       ->end()
     *     ->end()
     *
     * @return SourceJoinConditionDescendant
     */
    public function sameDocument($selector1Name, $selector2Name, $selector2Path)
    {
        return $this->addChild(new SourceJoinConditionSameDocument($this, 
            $selector1Name, $selector2Name, $selector2Path
        ));
    }
}
