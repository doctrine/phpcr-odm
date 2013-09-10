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
    public function property($field)
    {
        return $this->addChild(new Property($this, $field));
    }

    public function getNodeType()
    {
        return self::NT_SELECT;
    }
}

