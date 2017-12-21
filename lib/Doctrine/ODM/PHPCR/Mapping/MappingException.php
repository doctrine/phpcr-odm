<?php

namespace Doctrine\ODM\PHPCR\Mapping;

use Doctrine\Common\Persistence\Mapping\MappingException as BaseMappingException;
use Doctrine\ODM\PHPCR\PHPCRExceptionInterface;

/**
 * Mapping exception class.
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 *
 * @link        www.doctrine-project.com
 * @since       1.0
 *
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class MappingException extends BaseMappingException implements PHPCRExceptionInterface
{
    public static function classNotFound($className)
    {
        return new self("The class '$className' could not be found");
    }

    /**
     * Asking for the mapping of a field that does not exist.
     */
    public static function fieldNotFound($className, $fieldName)
    {
        return new self("The class '$className' does not have a field mapping for '$fieldName'");
    }

    /**
     * Non-annotation mappings could specify a fieldName that does not exist on the class.
     */
    public static function classHasNoField($className, $fieldName)
    {
        return new self("Invalid mapping: The class '$className' does not have a field named '$fieldName'");
    }

    public static function illegalChildName($className, $fieldName, $nodeName, $previous = null)
    {
        return new self("Invalid mapping: The field '$fieldName' of '$className' is configured to the illegal PHPCR node name '$nodeName'.", 0, $previous);
    }

    public static function associationNotFound($className, $fieldName)
    {
        return new self("The class '$className' does not have a association mapping for '$fieldName'");
    }

    public static function classIsNotAValidDocument($className)
    {
        return new self("Class '$className' is not a valid document or mapped super class.");
    }

    public static function reflectionFailure($document, \ReflectionException $previousException)
    {
        return new self("An error occurred in '$document'", 0, $previousException);
    }

    /**
     * @param string $document  The document's name
     * @param string $fieldName The name of the field that was already declared
     */
    public static function duplicateFieldMapping($document, $fieldName)
    {
        return new self("Property '$fieldName' in '$document' is declared twice.");
    }

    /**
     * @param string $document  The document's name
     * @param string $fieldName The name of the field that was already declared
     */
    public static function missingTypeDefinition($document, $fieldName)
    {
        return new self("Property '$fieldName' in '$document' must have a type attribute defined");
    }

    public static function classNotMapped($className)
    {
        return new self("Class '$className' is not mapped to a document");
    }

    public static function noTypeSpecified()
    {
        return new self('No type specified');
    }

    public static function assocOverlappingFieldDefinition($document, $fieldName, $overlappingFieldName)
    {
        return new self("The 'assoc' attributes may not overlap with field '$overlappingFieldName' for property '$fieldName' in '$document'.");
    }

    public static function assocOverlappingAssocDefinition($document, $fieldName, $overlappingAssoc)
    {
        return new self("The 'assoc' attributes may not overlap with assoc property '$overlappingAssoc' for property '$fieldName' in '$document'.");
    }

    public static function referrerWithoutReferencedBy($document, $fieldName)
    {
        return new self("The referrer field '$fieldName' in '$document' is missing the required 'referencedBy' attribute. If you want all referrers, use the immutable MixedReferrers mapping");
    }

    public static function referrerWithoutReferringDocument($document, $fieldName)
    {
        return new self("The referrer field '$fieldName' in '$document' is missing the required 'referringDocument' attribute. If you want all referrers, use the immutable MixedReferrers mapping");
    }

    public static function mappingNotFound($className, $fieldName)
    {
        return new self("No mapping found for field '$fieldName' in class '$className'.");
    }

    public static function identifierRequired($entityName, $what)
    {
        if (false !== ($parent = get_parent_class($entityName))) {
            return new self(sprintf(
                'No %s specified for Document "%s" sub class of "%s". Every Document must have an identifier/path.',
                $what, $entityName, $parent
            ));
        }

        return new self(sprintf(
            'No %s specified for Document "%s". Every Document must have an identifier/path.',
            $what, $entityName
        ));
    }

    public static function repositoryRequired($entityName)
    {
        return new self(sprintf(
            'Class %s is configured to have the REPOSITORY id strategy, but no repository class is configured',
            $entityName
        ));
    }

    public static function repositoryNotExisting($entityName, $repositoryClass)
    {
        return new self(sprintf(
            'Repository class "%s" configured on class %s does not exist',
            $repositoryClass, $entityName
        ));
    }

    public static function invalidTargetDocumentClass($targetDocument, $sourceDocument, $associationName)
    {
        return new self('The target-document '.$targetDocument." cannot be found in '".$sourceDocument.'#'.$associationName."'.");
    }

    public static function lifecycleCallbackMethodNotFound($className, $methodName)
    {
        return new self("Document '".$className."' has no method '".$methodName."' to be registered as lifecycle callback.");
    }

    public static function noTranslatorStrategy($className, $fieldNames)
    {
        return new self("Document '".$className."' does not have a translation strategy, but the fields ('".implode('\', \'', $fieldNames)."') have been set as translatable.");
    }

    public static function notReferenceable($className, $fieldName)
    {
        return new self(sprintf('Document "%s" is not referenceable. You can not map field "%s" to the UUID.', $className, $fieldName));
    }
}
