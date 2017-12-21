<?php

namespace Doctrine\ODM\PHPCR\Exception;

use Doctrine\ODM\PHPCR\PHPCRExceptionInterface;

/**
 * RuntimeException for the PHPCR-ODM.
 */
class RuntimeException extends \RuntimeException implements PHPCRExceptionInterface
{
    public static function invalidUuid($id, $class, $uuid)
    {
        return new self(sprintf(
            'Document %s of class %s has an invalid UUID "%s"',
            $id,
            $class,
            $uuid
        ));
    }
}
