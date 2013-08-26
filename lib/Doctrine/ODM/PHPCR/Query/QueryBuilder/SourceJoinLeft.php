<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;

class SourceJoinLeft extends From
{
    public function getNodeType()
    {
        return self::NT_SOURCE_JOIN_LEFT;
    }
}
