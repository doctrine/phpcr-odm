<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class OperandStaticFactory extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            'OperandStaticInterface' => array(1, 1),
        );
    }

    public function bindVariable($name)
    {
        return $this->addChild(new OperandStaticBindVariable($this, $name));
    }

    public function literal($value)
    {
        return $this->addChild(new OperandStaticLiteral($this, $value));
    }
}
