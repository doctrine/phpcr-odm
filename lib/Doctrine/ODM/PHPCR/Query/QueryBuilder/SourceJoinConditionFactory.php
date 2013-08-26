<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;

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

    public function descendant($descendantSelectorName, $ancestorSelectorName)
    {
        return $this->addChild(new SourceJoinConditionDescendant($this, 
            $descendantSelectorName, $ancestorSelectorName
        ));
    }

    public function equi($property1, $selector1Name, $property2, $selector2Name)
    {
        return $this->addChild(new SourceJoinConditionEqui($this,
            $property1, $selector1Name, $property2, $selector2Name
        ));
    }

    public function childDocument($childSelectorName, $parentSelectorName)
    {
        return $this->addChild(new SourceJoinConditionChildDocument($this, 
            $childSelectorName, $parentSelectorName
        ));
    }

    public function sameDocument($selector1Name, $selector2Name, $selector2Path)
    {
        return $this->addChild(new SourceJoinConditionSameDocument($this, 
            $selector1Name, $selector2Name, $selector2Path
        ));
    }
}
