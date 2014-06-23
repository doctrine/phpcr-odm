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

namespace Doctrine\ODM\PHPCR;

/**
 * Basic exception class for Doctrine PHPCR-ODM.
 */
class PHPCRException extends \Exception implements PHPCRExceptionInterface
{
    public static function unknownDocumentNamespace($documentNamespaceAlias)
    {
        return new self("Unknown Document namespace alias '$documentNamespaceAlias'.");
    }

    public static function documentManagerClosed()
    {
        return new self("The DocumentManager is closed.");
    }

    public static function cannotMoveByAssignment($objInfo)
    {
        return new self('Cannot move/copy children by assignment as it would be ambiguous. Please use the DocumentManager::move() or PHPCR\Session::copy() operations for this: '.$objInfo);
    }

    public static function invalidDocumentRepository($className)
    {
        return new self("Invalid repository class '".$className."'. It must be a Doctrine\Common\Persistence\ObjectRepository.");
    }

    public static function childFieldIsArray($className, $fieldName)
    {
        return new self(sprintf(
            'Child document is not stored correctly in a child property. Do not use array notation or a Collection in field "%s" of document "%s"',
            $fieldName,
            $className
        ));
    }

    public static function childFieldNoObject($className, $fieldName, $type)
    {
        return new self(sprintf(
            'A child field may only contain mapped documents, found <%s> in field "%s" of "%s"',
            $type,
            $fieldName,
            $className
        ));
    }

    public static function childrenFieldNoArray($className, $fieldName)
    {
        return new self(sprintf(
            'Children documents are not stored correctly in a children property. Use array notation or a Collection: field "%s" of "%s"',
            $fieldName,
            $className
        ));
    }

    public static function associationFieldNoArray($className, $fieldName)
    {
        return new self(sprintf(
            'Association documents are not stored correctly in an association property. Use array notation or a Collection: field "%s" of "%s"',
            $fieldName,
            $className
        ));
    }

    public static function childrenContainsNonObject($className, $fieldName, $type)
    {
        return new self(sprintf(
            'A children field may only contain mapped documents, found <%s> in field "%s" of "%s"',
            $type,
            $fieldName,
            $className
        ));
    }
}
