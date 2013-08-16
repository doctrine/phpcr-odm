<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class Select extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            'Property' => array(0, null)
        );
    }

    public function property($propertyName, $selectorName)
    {
        return $this->addChild(new Property($this, $propertyName, $selectorName));
    }
}

