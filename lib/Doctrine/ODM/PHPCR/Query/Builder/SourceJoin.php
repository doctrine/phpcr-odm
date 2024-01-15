<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * @IgnoreAnnotation("factoryMethod")
 */
class SourceJoin extends AbstractNode
{
    private string $joinType;

    public function __construct(AbstractNode $parent, string $joinType)
    {
        $this->joinType = $joinType;
        parent::__construct($parent);
    }

    public function getNodeType(): string
    {
        return self::NT_SOURCE;
    }

    /**
     * Specify the document source for the "left" side of a join.
     *
     * @factoryMethod
     */
    public function left(): SourceJoinLeft
    {
        return $this->addChild(new SourceJoinLeft($this));
    }

    /**
     * Specify the document source for the "right" side of a join.
     *
     * @factoryMethod
     */
    public function right(): SourceJoinRight
    {
        return $this->addChild(new SourceJoinRight($this));
    }

    /**
     * Specify the join condition.
     *
     * @factoryMethod
     */
    public function condition(): SourceJoinConditionFactory
    {
        return $this->addChild(new SourceJoinConditionFactory($this));
    }

    public function getJoinType(): string
    {
        return $this->joinType;
    }

    public function getCardinalityMap(): array
    {
        return [
            self::NT_SOURCE_JOIN_CONDITION_FACTORY => [1, 1],
            self::NT_SOURCE_JOIN_LEFT => [1, 1],
            self::NT_SOURCE_JOIN_RIGHT => [1, 1],
        ];
    }
}
