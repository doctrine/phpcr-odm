<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Factory node for "left" source in join.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class SourceJoinLeft extends From
{
    public function getNodeType()
    {
        return self::NT_SOURCE_JOIN_LEFT;
    }
}
