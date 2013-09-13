<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Append an additional "where" with an AND
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class WhereAnd extends Where
{
    public function getNodeType()
    {
        return self::NT_WHERE;
    }
}
