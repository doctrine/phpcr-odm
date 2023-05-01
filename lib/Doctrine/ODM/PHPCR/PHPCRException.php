<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Persistence\ObjectRepository;

/**
 * Basic exception class for Doctrine PHPCR-ODM.
 */
class PHPCRException extends \Exception implements PHPCRExceptionInterface
{
    public static function unknownDocumentNamespace(string $documentNamespaceAlias): self
    {
        return new self("Unknown Document namespace alias '$documentNamespaceAlias'.");
    }

    public static function documentManagerClosed(): self
    {
        return new self('The DocumentManager is closed.');
    }

    public static function cannotMoveByAssignment(string $objInfo): self
    {
        return new self('Cannot move/copy children by assignment as it would be ambiguous. Please use the DocumentManager::move() or PHPCR\Session::copy() operations for this: '.$objInfo);
    }

    public static function invalidDocumentRepository(string $className): self
    {
        return new self("Invalid repository class '".$className."'. It must implement ".ObjectRepository::class);
    }

    public static function childFieldIsArray(string $className, string $fieldName): self
    {
        return new self(sprintf(
            'Child document is not stored correctly in a child property. Do not use array notation or a Collection in field "%s" of document "%s"',
            $fieldName,
            $className
        ));
    }

    public static function childFieldNoObject(string $className, string $fieldName, string $type): self
    {
        return new self(sprintf(
            'A child field may only contain mapped documents, found <%s> in field "%s" of "%s"',
            $type,
            $fieldName,
            $className
        ));
    }

    public static function childrenFieldNoArray(string $className, string $fieldName): self
    {
        return new self(sprintf(
            'Children documents are not stored correctly in a children property. Use array notation or a Collection: field "%s" of "%s"',
            $fieldName,
            $className
        ));
    }

    public static function associationFieldNoArray(string $className, string $fieldName): self
    {
        return new self(sprintf(
            'Association documents are not stored correctly in an association property. Use array notation or a Collection: field "%s" of "%s"',
            $fieldName,
            $className
        ));
    }

    public static function childrenContainsNonObject(string $className, string $fieldName, string $type): self
    {
        return new self(sprintf(
            'A children field may only contain mapped documents, found <%s> in field "%s" of "%s"',
            $type,
            $fieldName,
            $className
        ));
    }
}
