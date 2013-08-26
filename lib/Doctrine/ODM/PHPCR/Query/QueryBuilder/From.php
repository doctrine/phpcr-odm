<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class From extends SourceFactory
{
    public function getCardinalityMap()
    {
        return array(
            self::NT_SOURCE => array(1, 1),
        );
    }

    public function getNodeType()
    {
        return self::NT_FROM;
    }

}
