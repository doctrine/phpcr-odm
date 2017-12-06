<?php

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\Common\Util\ClassUtils;
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
            $document ? ClassUtils::getClass($document) : 'null'
        );

        return new self($message);
    }

    public static function noIdNoParent($document, $parent)
    {
        $message = sprintf(
            'Property "%s" mapped as ParentDocument may not be empty in document of class "%s"',
            $parent,
            $document ? ClassUtils::getClass($document) : 'null'
        );

        return new self($message);
    }

    public static function noIdNoName($document, $fieldName)
    {
        $message = sprintf(
            'NodeName property "%s" may not be empty in document of class "%s"',
            $fieldName,
            $document ? ClassUtils::getClass($document) : 'null'
        );

        return new self($message);
    }

    public static function parentIdCouldNotBeDetermined($document, $parent, $parentObject)
    {
        $parentType = is_object($parentObject) ? ClassUtils::getClass($parentObject) : $parentObject;
        $message = sprintf(
            'ParentDocument property "%s" of document of class "%s" contains an ' .
            'object for which no ID could be found',
            $parent,
            $document ? ClassUtils::getClass($document) : 'null',
            $parentType
        );

        return new self($message);
    }

    public static function illegalName($document, $fieldName, $nodeName)
    {
        $message = sprintf(
            'NodeName property "%s" of document "%s" contains the illegal PHPCR value "%s".',
            $fieldName,
            $document ? ClassUtils::getClass($document) : 'null',
            $nodeName
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
            $childDocument ? ClassUtils::getClass($childDocument) : 'null',
            $parentId,
            $parentFieldName,
            $fieldNodeName,
            $childNodeName
        );

        return new self($message);
    }
}
