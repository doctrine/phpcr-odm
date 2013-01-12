<?php

namespace Doctrine\ODM\PHPCR\Id;

class IdException extends \RuntimeException
{
    public static function noIdentificationParameters($document)
    {
        $message = sprintf(
            'Document of class "%s" has no identification metadata, you must either '.
            'designate either  @NodeName and @ParentDocument @Id',
            get_class($document)
        );

        return new self($message);
    }

    public static function noIdNoParent($document, $name)
    {
        $message = sprintf(
            'Cannot identify @ParentDocument variable for Document of class "%s"'.
            'with name "%s"',
            get_class($document),
            $name
        );

        return new self($message);
    }

    public static function noIdNoName($document)
    {
        $message = sprintf(
            'Cannot identify @NodeName variable for Document of class "%s"',
            get_class($document)
        );

        return new self($message);
    }

    public static function parentIdCouldNotBeDetermined($document, $name)
    {
        $message = sprintf(
            'Parent ID for document of class "%s" with name "%s" could not be determined. '.
            'Make sure to persist the parent document before persisting this document.',
            get_class($document),
            $name
        );

        return new self($message);
    }
}
