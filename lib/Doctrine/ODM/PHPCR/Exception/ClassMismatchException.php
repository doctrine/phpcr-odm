<?php

namespace Doctrine\ODM\PHPCR\Exception;

/**
 * The requested class did not match the data found in the repository.
 */
class ClassMismatchException extends RuntimeException
{
    public static function incompatibleClasses(string $id, string $nodeClassName, string $className): self
    {
        return new self(sprintf(
            'Document at %s is of class %s incompatible with class %s',
            $id,
            $nodeClassName,
            $className
        ));
    }
}
