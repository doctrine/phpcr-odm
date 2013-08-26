<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class Select extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            self::NT_PROPERTY => array(0, null)
        );
    }

    public function property($propertyName, $selectorName)
    {
        return $this->addChild(new Property($this, $propertyName, $selectorName));
    }

    public function getNodeType()
    {
        return self::NT_SELECT;
    }
}

