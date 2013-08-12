<?php

class Builder extends Node
{
    public function getCardinatlities()
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
        $where = new Fundamental\Where;
        $this->addChild($where)
    }
}
