<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;

class SourceJoinRight extends From
{
    public function getNodeType()
    {
        return self::NT_SOURCE_JOIN_RIGHT;
    }
}
