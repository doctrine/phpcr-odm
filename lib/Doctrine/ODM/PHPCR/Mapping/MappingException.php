<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\PHPCR\Mapping;

/**
 * Mapping exception class
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class MappingException extends \Exception
{
    public static function classNotFound($className)
    {
        return new self("The class '$className' could not be found");
    }

    public static function fieldNotFound($className, $fieldName)
    {
        return new self("The class '$className' does not have a field mapping for '$fieldName'");
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
        return new self("Property '$fieldName'. in .'$document'. was already declared, but it must be declared only once");
    }

    /**
     * @param string $document  The document's name
     * @param string $fieldName The name of the field that was already declared
     */
    public static function missingTypeDefinition($document, $fieldName)
    {
        return new self("Property '$fieldName' in '$document' must have a type attribute defined");
    }

    public static function fileMappingDriversRequireConfiguredDirectoryPath($path)
    {
        return new self("File mapping drivers must have a valid directory path, however the given path '$path' seems to be incorrect!");
    }

    public static function classNotMapped($className)
    {
        return new self("Class '$className' is not mapped to a document");
    }

    public static function pathRequired()
    {
        return new self('Path is required');
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

    public static function mappingNotFound($className, $fieldName)
    {
        return new self("No mapping found for field '$fieldName' in class '$className'.");
    }

    public static function mappingFileNotFound($className, $filedName)
    {
        return new self("No mapping file '$filedName' found for class '$className'.");
    }

    public static function identifierRequired($entityName)
    {
        if (false !== ($parent = get_parent_class($entityName))) {
            return new self(sprintf(
                'No identifier/path specified for Document "%s" sub class of "%s". Every Document must have an identifier/path.',
                $entityName, $parent
            ));
        }

        return new self(sprintf(
            'No identifier/path specified for Document "%s". Every Document must have an identifier/path.',
            $entityName
        ));

    }

    public static function invalidTargetDocumentClass($targetDocument, $sourceDocument, $associationName)
    {
        return new self("The target-document " . $targetDocument . " cannot be found in '" . $sourceDocument."#".$associationName."'.");
    }

    public static function lifecycleCallbackMethodNotFound($className, $methodName)
    {
        return new self("Document '" . $className . "' has no method '" . $methodName . "' to be registered as lifecycle callback.");
    }

    public static function noTranslatorStrategy($className, $fieldNames)
    {
        return new self("Document '" .$className."' does not have a translation strategy, but the fields ('".implode(', ', $fieldNames)."' have been set as translatable.");
    }
}
