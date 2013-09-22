<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use Doctrine\ODM\PHPCR\PHPCRBadMethodCallException;

/**
 * Abstract factory/node class for Sources.
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
     * Document source:
     *
     *   $qb->from()->document('My/Document/Class', 'my_alias')
     *
     * Select documents of specified class. The selector name is mandatory
     * and will be used to reference documents selected from this source.
     *
     * @param string $documentFqn
     * @param string $alias
     *
     * @factoryMethod
     * @return SourceDocument
     */
    public function document($documentFqn, $alias)
    {
        return $this->addChild(new SourceDocument($this, $documentFqn, $alias));
    }

    /**
     * Inner Join:
     *
     *   $qb->from()
     *     ->joinInner()
     *       ->left()->document('My/Document/Class/One', 'alias_1')->end()
     *       ->right()->document('My/Document/Class/Two', 'alias_2')->end()
     *       ->condition()
     *         ->equi('prop_1','alias_1', 'prop_2', 'alias_2')
     *       ->end()
     *     ->end()
     *
     * @factoryMethod
     * @return SourceJoin
     */
    public function joinInner()
    {
        throw new PHPCRBadMethodCallException(__METHOD__.' not supported yet');

        return $this->addChild(new SourceJoin($this,
            QOMConstants::JCR_JOIN_TYPE_INNER
        ));
    }

    /**
     * Left Outer Join:
     *
     *   $qb->from()
     *     ->joinLeftOuter()
     *       ->left()->document('My/Document/Class/One', 'alias_1')->end()
     *       ->right()->document('My/Document/Class/Two', 'alias_2')->end()
     *       ->condition()
     *         ->equi('prop_1','alias_1', 'prop_2', 'alias_2')
     *       ->end()
     *     ->end()
     *
     * @factoryMethod
     * @return SourceJoin
     */
    public function joinLeftOuter()
    {
        throw new PHPCRBadMethodCallException(__METHOD__.' not supported yet');

        return $this->addChild(new SourceJoin($this, 
            QOMConstants::JCR_JOIN_TYPE_LEFT_OUTER
        ));
    }

    /**
     * Right Outer Join:
     *
     *   $qb->from()
     *     ->joinRightOuter()
     *       ->left()->document('My/Document/Class/One', 'alias_1')->end()
     *       ->right()->document('My/Document/Class/Two', 'alias_2')->end()
     *       ->condition()
     *         ->equi('prop_1','alias_1', 'prop_2', 'alias_2')
     *       ->end()
     *     ->end()
     *
     * @factoryMethod
     * @return SourceJoin
     */
    public function joinRightOuter()
    {
        throw new PHPCRBadMethodCallException(__METHOD__.' not supported yet');

        return $this->addChild(new SourceJoin($this, 
            QOMConstants::JCR_JOIN_TYPE_RIGHT_OUTER
        ));
    }
}
