<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

Use Doctrine\ODM\PHPCR\Query\Builder\Source;

class SourceJoinLeft extends From
{
    public function getNodeType()
    {
        return self::NT_SOURCE_JOIN_LEFT;
    }
}
