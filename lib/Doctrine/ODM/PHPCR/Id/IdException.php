<?php

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\PHPCR\PHPCRException;

class IdException extends PHPCRException
{
    public static function noIdentificationParameters(object $document, string $parentField, string $nodename): self
    {
        $message = sprintf(
            'Property "%s" mapped as ParentDocument and property "%s" mapped as Nodename '.
                'may not be empty in document of class "%s"',
            $parentField,
            $nodename,
            ClassUtils::getClass($document)
        );

        return new self($message);
    }

    public static function noIdNoParent(object $document, string $parentField): self
    {
        $message = sprintf(
            'Property "%s" mapped as ParentDocument may not be empty in document of class "%s"',
            $parentField,
            ClassUtils::getClass($document)
        );

        return new self($message);
    }

    public static function noIdNoName(object $document, string $fieldName): self
    {
        $message = sprintf(
            'Nodename property "%s" may not be empty in document of class "%s"',
            $fieldName,
            ClassUtils::getClass($document)
        );

        return new self($message);
    }

    public static function parentIdCouldNotBeDetermined(object $document, string $parent, object $parentObject): self
    {
        $message = sprintf(
            'ParentDocument property "%s" of document of class "%s" contains an '.
            'object with class %s for which no ID could be found',
            $parent,
            ClassUtils::getClass($document),
            ClassUtils::getClass($parentObject)
        );

        return new self($message);
    }

    public static function illegalName(object $document, string $fieldName, string $nodeName, ?\Throwable $previous = null): self
    {
        $message = sprintf(
            'Nodename property "%s" of document "%s" contains the illegal PHPCR value "%s".',
            $fieldName,
            ClassUtils::getClass($document),
            $nodeName
        );

        return new self($message, 0, $previous);
    }

    public static function conflictingChildName(
        string $parentId,
        string $parentFieldName,
        string $fieldNodeName,
        object $childDocument,
        string $childNodeName
    ): self {
        $message = sprintf(
            '%s discovered as new child of %s in field "%s" has a node name '.
            'mismatch. The mapping says "%s" but the child was assigned "%s".',
            ClassUtils::getClass($childDocument),
            $parentId,
            $parentFieldName,
            $fieldNodeName,
            $childNodeName
        );

        return new self($message);
    }
}
