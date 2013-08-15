<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;

/**
 * $from->joinInner()->left()->document()->
 */
class SourceJoin extends AbstractNode implements SourceInterface
{
    protected $type;

    public function __construct($parent, $type)
    {
        $this->type = $type;
        parent::__construct($parent);
    }

    public function left()
    {
        return $this->addChild(new SourceJoinLeft($this));
    }

    public function right()
    {
        return $this->addChild(new SourceJoinRight($this));
    }

    public function condition()
    {
        return $this->addChild(new SourceJoinCondition($this));
    }

    public function getCardinalityMap()
    {
        return array(
            'JoinCondition' => array(1, 1),
            'SourceJoinLeft' => array(1, 1),
            'SourceJoinRight' => array(1, 1),
        );
    }
}

