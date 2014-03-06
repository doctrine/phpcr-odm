<?php

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\PHPCRException;

class IdException extends PHPCRException
{
    public static function noIdentificationParameters($document, $parent, $nodename)
    {
        $message = sprintf(
            'Property "%s" mapped as ParentDocument and property "%s" mapped as NodeName '.
                'may not be empty in document of class "%s"',
            $parent,
            $nodename,
            get_class($document)
        );

        return new self($message);
    }

    public static function noIdNoParent($document, $parent)
    {
        $message = sprintf(
            'Property "%s" mapped as ParentDocument may not be empty in document of class "%s"',
            $parent,
            get_class($document)
        );

        return new self($message);
    }

    public static function noIdNoName($document, $fieldName)
    {
        $message = sprintf(
            'NodeName property "%s" may not be empty in document of class "%s"',
            $fieldName,
            get_class($document)
        );

        return new self($message);
    }

    public static function parentIdCouldNotBeDetermined($document, $parent, $parentObject)
    {
        $parentType = is_object($parentObject) ? get_class($parentObject) : $parentObject;
        $message = sprintf(
            'ParentDocument property "%s" of document of class "%s" contains an ' .
            'object for which no ID could be found',
            $parent,
            get_class($document),
            $parentType
        );

        return new self($message);
    }

    public static function conflictingChildName(
        $parentId,
        $parentFieldName,
        $fieldNodeName,
        $childDocument,
        $childNodeName)
    {
        $message = sprintf(
            '%s discovered as new child of %s in field "%s" has a node name ' .
            'mismatch. The mapping says "%s" but the child was assigned "%s".',
            get_class($childDocument),
            $parentId,
            $parentFieldName,
            $fieldNodeName,
            $childNodeName
        );

        return new self($message);
    }
}
