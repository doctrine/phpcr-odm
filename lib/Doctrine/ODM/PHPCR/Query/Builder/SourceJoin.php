<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * $from->joinInner()->left()->document()->
 *
 * @IgnoreAnnotation("factoryMethod")
 */
class SourceJoin extends AbstractNode
{
    protected $joinType;

    public function __construct($parent, $joinType)
    {
        $this->joinType = $joinType;
        parent::__construct($parent);
    }

    public function getNodeType()
    {
        return self::NT_SOURCE;
    }

    /**
     * Specify the document source for the "left" side of a join.
     *
     * @factoryMethod
     * @return SourceJoinLeft
     */
    public function left()
    {
        return $this->addChild(new SourceJoinLeft($this));
    }

    /**
     * Specify the document source for the "right" side of a join.
     *
     * @factoryMethod
     * @return SourceJoinRight
     */
    public function right()
    {
        return $this->addChild(new SourceJoinRight($this));
    }

    /**
     * Specify the join condition.
     *
     * @factoryMethod
     * @return SourceJoinConditionFactory
     */
    public function condition()
    {
        return $this->addChild(new SourceJoinConditionFactory($this));
    }

    public function getJoinType()
    {
        return $this->joinType;
    }

    public function getCardinalityMap()
    {
        return array(
            self::NT_SOURCE_JOIN_CONDITION_FACTORY => array(1, 1),
            self::NT_SOURCE_JOIN_LEFT => array(1, 1),
            self::NT_SOURCE_JOIN_RIGHT => array(1, 1),
        );
    }
}
