<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Exception\ClassMismatchException;
use PHPCR\NodeInterface;

interface DocumentClassMapperInterface
{
    /**
     * Determine the class name from a given node.
     *
     * @param string|null $className explicit class to use. If set, this
     *                               class or a subclass of it has to be used. If this is not possible,
     *                               an InvalidArgumentException has to be thrown.
     *
     * @return string $className The class configured for this node if a definition could be found.
     *                If nothing else can be determined, returns Generic
     *
     * @throws ClassMismatchException if $node represents a class that is not
     *                                a descendant of $className
     */
    public function getClassName(DocumentManagerInterface $dm, NodeInterface $node, string $className = null): string;

    /**
     * Write any relevant meta data into the node to be able to map back to a class name later.
     */
    public function writeMetadata(DocumentManagerInterface $dm, NodeInterface $node, string $className): void;

    /**
     * Check if the document is instance of the specified $className and
     * throw exception if not.
     *
     * @throws ClassMismatchException if document is not of type $className
     */
    public function validateClassName(DocumentManagerInterface $dm, object $document, string $className): void;
}
