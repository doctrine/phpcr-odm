<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use Doctrine\ODM\PHPCR\Exception\BadMethodCallException;

/**
 * Abstract factory node class for Sources.
 *
 * In PHPCR terms there is only ever one "source", which
 * can either be a "node type" source, or a join.
 *
 * In the ODM the concept of "node type" is replaced with the
 * Document type
 *
 * @IgnoreAnnotation("factoryMethod")
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
abstract class SourceFactory extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            self::NT_SOURCE => array(1, 1)
        );
    }

    /**
     * Select documents of specified class. The selector name is mandatory
     * and will be used to reference documents selected from this source.
     *
     * <code>
     * $qb->from()->document('My/Document/Class', 'my_selector_name')
     * </code>
     *
     * @param string $documentFqn - Fully qualified class name for document.
     * @param string $selectorName - Selector name.
     *
     * @factoryMethod SourceDocument
     * @return SourceDocument
     */
    public function document($documentFqn, $alias)
    {
        return $this->addChild(new SourceDocument($this, $documentFqn, $alias));
    }

    /**
     * Note that his is currently disabled due to API uncertainty.
     *
     * <code>
     * $qb->from()
     *   ->joinInner()
     *     ->left()->document('My/Document/Class/One', 'sel_1')->end()
     *     ->right()->document('My/Document/Class/Two', 'sel_2')->end()
     *     ->condition()->equi('sel_1.prop_1', 'sel_2.prop_2');
     * </code>
     *
     * @factoryMethod SourceJoin
     * @return SourceJoin
     */
    public function joinInner()
    {
        throw new BadMethodCallException(__METHOD__.' not supported yet');

        return $this->addChild(new SourceJoin($this,
            QOMConstants::JCR_JOIN_TYPE_INNER
        ));
    }

    /**
     * Left Outer Join.
     *
     * Note that his is currently disabled due to API uncertainty.
     *
     * <code>
     * $qb->from()
     *   ->joinLeftOuter()
     *     ->left()->document('My/Document/Class/One', 'sel_1')->end()
     *     ->right()->document('My/Document/Class/Two', 'sel_2')->end()
     *     ->condition()->equi('sel_1.prop_1', 'sel_2.prop_2');
     * </code>
     *
     * @factoryMethod SourceJoin
     * @return SourceJoin
     */
    public function joinLeftOuter()
    {
        throw new BadMethodCallException(__METHOD__.' not supported yet');

        return $this->addChild(new SourceJoin($this,
            QOMConstants::JCR_JOIN_TYPE_LEFT_OUTER
        ));
    }

    /**
     * Right Outer Join.
     *
     * Note that his is currently disabled due to API uncertainty.
     *
     * <code>
     * $qb->from()
     *   ->joinRightOuter()
     *     ->left()->document('My/Document/Class/One', 'sel_1')->end()
     *     ->right()->document('My/Document/Class/Two', 'sel_2')->end()
     *     ->condition()->equi('sel_1.prop_1', 'sel_2.prop_2');
     * </code>
     *
     * @factoryMethod SourceJoin
     * @return SourceJoin
     */
    public function joinRightOuter()
    {
        throw new BadMethodCallException(__METHOD__.' not supported yet');

        return $this->addChild(new SourceJoin($this,
            QOMConstants::JCR_JOIN_TYPE_RIGHT_OUTER
        ));
    }
}
