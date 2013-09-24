<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Factory node for appending additional "wheres" with an OR
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class WhereOr extends Where
{
    public function getNodeType()
    {
        return self::NT_WHERE;
    }
}
