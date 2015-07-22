<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Factory node for "right" source in join.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class SourceJoinRight extends From
{
    public function getNodeType()
    {
        return self::NT_SOURCE_JOIN_RIGHT;
    }
}
