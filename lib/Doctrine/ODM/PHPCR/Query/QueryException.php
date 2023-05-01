<?php

namespace Doctrine\ODM\PHPCR\Query;

use Doctrine\ODM\PHPCR\PHPCRException;

/**
 * @author Daniel Leech <daniel@dantleech.com>
 */
class QueryException extends PHPCRException
{
    public static function hydrationModeNotKnown(int $hydrationMode): self
    {
        return new self(sprintf(
            'Hydration mode "%s" not recognized, must be the value of one of '.
            'the Doctrine\ODM\PHPCR\Query::HYDRATE_ constants',
            $hydrationMode
        ));
    }

    public static function nonUniqueResult(): self
    {
        return new self('Expected unique result');
    }
}
