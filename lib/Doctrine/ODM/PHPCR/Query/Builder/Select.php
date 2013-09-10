<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

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
     * @return Field
     */
    public function field($field)
    {
        return $this->addChild(new Field($this, $field));
    }

    public function getNodeType()
    {
        return self::NT_SELECT;
    }
}

