<?php

namespace Doctrine\ODM\PHPCR\Exception;

use Doctrine\ODM\PHPCR\PHPCRExceptionInterface;

/**
 * The requested class did not match the data found in the repository.
 */
class ClassMismatchException extends RuntimeException implements PHPCRExceptionInterface
{
    public static function incompatibleClasses($id, $nodeClassName, $className)
    {
        return new self(sprintf(
            'Document at %s is of class %s incompatible with class %s',
            $id,
            $nodeClassName,
            $className
        ));
    }
}
