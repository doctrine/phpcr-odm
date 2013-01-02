<?php

namespace Doctrine\ODM\PHPCR\Query;

class QueryBuilderException extends \Exception
{
    public static function unknownPart($part, $parts)
    {
        $message = sprintf('Unknown query part "%s", must be one of "%s"',
            $part, implode(', ', $parts)
        );

        return new self($message);
    }

    public static function cannotJoinWithNoFrom()
    {
        $message = 'Cannot perform a join without a previous call to from';

        return new self($message);
    }
}
