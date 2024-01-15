<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * Factory node for where.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class Where extends ConstraintFactory
{
    public function getNodeType(): string
    {
        return self::NT_WHERE;
    }
}
