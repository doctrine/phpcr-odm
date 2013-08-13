<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class Builder extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            'Select' => array(0, null),    // 1..*
            'From' => array(1, 1),         // 1..1
            'Where' => array(0, null),     // 0..*
            'OrderBy' => array(0, null),   // 0..*
        );
    }

    public function where()
    {
        return $this->addChild(new Where($this));
    }

    public function from()
    {
        return $this->addChild(new From($this));
    }

    public function select()
    {
        return $this->addChild(new Select($this));
    }

    public function orderBy()
    {
        return $this->addChild(new OrderBy($this));
    }
}
