<?php

namespace Doctrine\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\PHPCRExceptionInterface;
use Doctrine\Persistence\Mapping\MappingException as BaseMappingException;

/**
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class MappingException extends BaseMappingException implements PHPCRExceptionInterface
{
    public static function classNotFound(string $className): self
    {
        return new self("The class '$className' could not be found");
    }

    /**
     * Asking for the mapping of a field that does not exist.
     */
    public static function fieldNotFound(string $documentClass, string $fieldName): self
    {
        return new self("The class '$documentClass' does not have a field mapping for '$fieldName'");
    }

    /**
     * XML/YAML mappings can specify a fieldName that does not exist on the class.
     */
    public static function classHasNoField(string $documentClass, string $fieldName): self
    {
        return new self("Invalid mapping: The class '$documentClass' does not have a field named '$fieldName'");
    }

    public static function illegalChildName(string $documentClass, string $fieldName, string $nodeName, \Throwable $previous = null): self
    {
        return new self("Invalid mapping: The field '$fieldName' of '$documentClass' is configured to the illegal PHPCR node name '$nodeName'.", 0, $previous);
    }

    public static function associationNotFound(string $documentClass, string $fieldName): self
    {
        return new self("The class '$documentClass' does not have a association mapping for '$fieldName'");
    }

    public static function classIsNotAValidDocument(string $documentClass): self
    {
        return new self("Class '$documentClass' is not a valid document or mapped super class.");
    }

    public static function duplicateFieldMapping(string $documentClass, string $fieldName): self
    {
        return new self("Property '$fieldName' in '$documentClass' is declared twice.");
    }

    public static function missingTypeDefinition(string $documentClass, string $fieldName): self
    {
        return new self("Property '$fieldName' in '$documentClass' must have a type attribute defined");
    }

    public static function classNotMapped(string $className): self
    {
        return new self("Class '$className' is not mapped to a document");
    }

    public static function assocOverlappingFieldDefinition(string $documentClass, string $fieldName, string $overlappingFieldName): self
    {
        return new self("The 'assoc' attributes may not overlap with field '$overlappingFieldName' for property '$fieldName' in '$documentClass'.");
    }

    public static function assocOverlappingAssocDefinition(string $documentClass, string $fieldName, string $overlappingAssoc): self
    {
        return new self("The 'assoc' attributes may not overlap with assoc property '$overlappingAssoc' for property '$fieldName' in '$documentClass'.");
    }

    public static function referrerWithoutReferencedBy(string $documentClass, string $fieldName): self
    {
        return new self("The referrer field '$fieldName' in '$documentClass' is missing the required 'referencedBy' attribute. If you want all referrers, use the immutable MixedReferrers mapping");
    }

    public static function referrerWithoutReferringDocument(string $documentClass, string $fieldName): self
    {
        return new self("The referrer field '$fieldName' in '$documentClass' is missing the required 'referringDocument' attribute. If you want all referrers, use the immutable MixedReferrers mapping");
    }

    public static function identifierRequired(string $documentClass, string $what): self
    {
        if (false !== ($parentClass = get_parent_class($documentClass))) {
            return new self(sprintf(
                'No %s specified for Document "%s" sub class of "%s". Every Document must have an identifier/path.',
                $what,
                $documentClass,
                $parentClass
            ));
        }

        return new self(sprintf(
            'No %s specified for Document "%s". Every Document must have an identifier/path.',
            $what,
            $documentClass
        ));
    }

    public static function repositoryRequired(string $documentClass): self
    {
        return new self(sprintf(
            'Class %s is configured to have the REPOSITORY id strategy, but no repository class is configured',
            $documentClass
        ));
    }

    public static function repositoryNotExisting(string $documentClass, string $repositoryClass): self
    {
        return new self(sprintf(
            'Repository class "%s" configured on class %s does not exist',
            $repositoryClass,
            $documentClass
        ));
    }

    public static function invalidTargetDocumentClass(string $targetDocument, string $sourceDocument, string $associationName): self
    {
        return new self('The target-document '.$targetDocument." cannot be found in '".$sourceDocument.'#'.$associationName."'.");
    }

    public static function lifecycleCallbackMethodNotFound(string $documentClass, string $methodName): self
    {
        return new self("Document '".$documentClass."' has no method '".$methodName."' to be registered as lifecycle callback.");
    }

    /**
     * @param string[] $fieldNames
     */
    public static function noTranslatorStrategy(string $documentClass, array $fieldNames): self
    {
        return new self("Document '".$documentClass."' does not have a translation strategy, but the fields ('".implode('\', \'', $fieldNames)."') have been set as translatable.");
    }

    public static function notReferenceable(string $documentClass, string $fieldName): self
    {
        return new self(sprintf('Document "%s" is not referenceable. You can not map field "%s" to the UUID.', $documentClass, $fieldName));
    }
}
