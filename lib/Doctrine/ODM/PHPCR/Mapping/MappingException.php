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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\PHPCR\Mapping;

/**
 * Mapping exception class
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class MappingException extends \Exception
{
    public static function classNotFound($className)
    {
        return new self('The class: ' . $className . ' could not be found');
    }

    public static function classIsNotAValidDocument($className)
    {
        return new self('Class '.$className.' is not a valid document or mapped super class.');
    }

    public static function reflectionFailure($document, \ReflectionException $previousException)
    {
        return new self('An error occurred in ' . $document, 0, $previousException);
    }

    /**
     * @param string $document The document's name
     * @param string $fieldName The name of the field that was already declared
     */
    public static function duplicateFieldMapping($document, $fieldName)
    {
        return new self('Property "'.$fieldName.'" in "'.$document.'" was already declared, but it must be declared only once');
    }

    /**
     * @param string $document The document's name
     * @param string $fieldName The name of the field that was already declared
     */
    public static function missingTypeDefinition($document, $fieldName)
    {
        return new self('Property "'.$fieldName.'" in "'.$document.'" must have a type attribute defined');
    }

    public static function fileMappingDriversRequireConfiguredDirectoryPath()
    {
        return new self('File mapping drivers must have a valid directory path, however the given path seems to be incorrect!');
    }

    public static function classNotMapped($className)
    {
        return new self('Class ' . $className . ' is not mapped to a document');
    }

    public static function noTypeSpecified()
    {
        return new self();
    }

    public static function mappingNotFound($className, $fieldName)
    {
        return new self("No mapping found for field '$fieldName' in class '$className'.");
    }

    public static function mappingFileNotFound($className, $filedName)
    {
        return new self("No mapping file '$filedName' found for class '$className'.");
    }
}
