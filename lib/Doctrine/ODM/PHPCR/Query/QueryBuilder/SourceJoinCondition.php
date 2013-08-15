<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;

class SourceJoinCondition extends AbstractNode implements SourceInterface
{
    public function getCardinalityMap()
    {
        return array(
            'SourceConditionInterface' => array(1, 1)
        );
    }

    public function descendant($descendantSelectorName, $ancestorSelectorName)
    {
        return $this->addChild(new SourceConditionDescendant($this, 
            $descendantSelectorName, $ancestorSelectorName
        ));
    }

    public function equi($property1, $selector1Name, $property2, $selector2Name)
    {
        return $this->addChild(new SourceConditionEqui($this,
            $property1, $selector1Name, $property2, $selector2Name
        ));
    }

    public function childDocument($childSelectorName)
    {
        return $this->addChild(new SourceConditionChildDocument($this, 
            $childSelectorName
        ));
    }

    public function sameDocument($selector1Name, $selector2Name, $selector2Path)
    {
        return $this->addChild(new SourceConditionSameDocument($this, 
            $selector1Name, $selector2Name, $selector2Path
        ));
    }
}
