<?php

namespace Doctrine\ODM\PHPCR\Query;

/**
 * Mapping exception class
 *
 * @author      Daniel Leech <daniel@dantleech.com>
 */
class QueryException extends \Exception
{
    public static function hydrationModeNotKnown($hydrationMode)
    {
        return new self(sprintf(
            'Hydration mode "%s" not recognized, must be the value of one of '.
            'the Doctrine\ODM\PHPCR\Query::HYDRATE_ constants',
            $hydrationMode
        ));
    }

    public static function nonUniqueResult()
    {
        return new self(sprintf('Expected unique unique result'));
    }

    public static function noResult()
    {
        return new self(sprintf('Expected result from query, didn\'t get one.'));
    }

    public static function notImplemented($method)
    {
        return new self(sprintf('Query Method "%s" is not implemented (yet)', $method));
    }
}
