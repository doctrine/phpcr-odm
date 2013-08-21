<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class From extends SourceFactory
{
    public function getCardinalityMap()
    {
        return array(
            'SourceInterface' => array(1, 1),
        );
    }

}
