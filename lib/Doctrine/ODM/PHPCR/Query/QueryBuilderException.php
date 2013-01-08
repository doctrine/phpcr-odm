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

    public static function cannotGetQueryWhenNoSourceSet()
    {
        $message = 'Cannot getQuery when no "from" (source) has been specified';

        return new self($message);
    }

    public static function notYetSupported($method, $message = null)
    {
        $message = sprintf('QueryBuilder method "%s" has not yet been implemented%s',
            $method,
            $message ? ': '.$message : ''
        );

        return new self($message);
    }
}
