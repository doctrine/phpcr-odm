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

    /**
     * @factoryMethod
     * @return Property
     */
    public function property($selectorName, $propertyName)
    {
        return $this->addChild(new Property($this, $selectorName, $propertyName));
    }

    public function getNodeType()
    {
        return self::NT_SELECT;
    }
}

