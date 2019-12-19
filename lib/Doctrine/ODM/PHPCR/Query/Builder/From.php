<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

/**
 * The From node specifies the document source (or sources in the
 * case of a join).
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class From extends SourceFactory
{
    public function getCardinalityMap()
    {
        return [
            self::NT_SOURCE => [1, 1],
        ];
    }

    public function getNodeType()
    {
        return self::NT_FROM;
    }
}
