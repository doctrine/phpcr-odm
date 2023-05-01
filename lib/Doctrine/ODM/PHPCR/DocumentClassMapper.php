<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Exception\ClassMismatchException;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;

/**
 * Stores the class mapping in the phpcr:class attribute.
 *
 * If no class is found, use 'Doctrine\ODM\PHPCR\Document\Generic'
 */
class DocumentClassMapper implements DocumentClassMapperInterface
{
    private function expandClassName(DocumentManagerInterface $dm, string $className = null): ?string
    {
        if (null === $className) {
            return null;
        }

        if (false !== strpos($className, ':')) {
            $className = $dm->getClassMetadata($className)->getName();
        }

        return $className;
    }

    public function getClassName(DocumentManagerInterface $dm, NodeInterface $node, string $className = null): string
    {
        $className = $this->expandClassName($dm, $className);

        if ($node->hasProperty('phpcr:class')) {
            $nodeClassName = $node->getProperty('phpcr:class')->getString();
            \assert(is_string($nodeClassName));

            if (!empty($className)
                && $nodeClassName !== $className
                && !is_subclass_of($nodeClassName, $className)
            ) {
                throw ClassMismatchException::incompatibleClasses($node->getPath(), $nodeClassName, $className);
            }
            $className = $nodeClassName;
        }

        // default to the built in generic document class
        if (empty($className)) {
            $className = Document\Generic::class;
        }

        return $className;
    }

    public function writeMetadata(DocumentManagerInterface $dm, NodeInterface $node, string $className): void
    {
        $className = $this->expandClassName($dm, $className);

        if (Document\Generic::class !== $className) {
            $node->setProperty('phpcr:class', $className, PropertyType::STRING);

            $class = $dm->getClassMetadata($className);
            $node->setProperty(
                'phpcr:classparents',
                $class->getParentClasses(),
                PropertyType::STRING
            );
        }
    }

    public function validateClassName(DocumentManagerInterface $dm, object $document, string $className): void
    {
        $className = $this->expandClassName($dm, $className);

        if (!$document instanceof $className) {
            throw ClassMismatchException::incompatibleClasses(
                $dm->getUnitOfWork()->determineDocumentId($document),
                get_class($document),
                $className
            );
        }
    }
}
