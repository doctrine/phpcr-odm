<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class OperandDynamicLowerCase extends OperandDynamicFactory implements OperandDynamicInterface
{
    public function getCardinalityMap()
    {
        return array(
            'OperandDynamicInterface' => array(1, 1),    // 1..*
        );
    }
}
