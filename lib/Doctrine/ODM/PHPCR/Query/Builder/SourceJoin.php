<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;

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
     * "Left" source
     *
     * @factoryMethod
     * @return SourceJoinLeft
     */
    public function left()
    {
        return $this->addChild(new SourceJoinLeft($this));
    }

    /**
     * "Right" source
     *
     * @factoryMethod
     * @return SourceJoinRight
     */
    public function right()
    {
        return $this->addChild(new SourceJoinRight($this));
    }

    /**
     * Join condition
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
