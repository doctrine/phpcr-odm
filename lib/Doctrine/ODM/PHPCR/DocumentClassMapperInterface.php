<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Exception\ClassMismatchException;
use PHPCR\NodeInterface;

interface DocumentClassMapperInterface
{
    /**
     * Determine the class name from a given node.
     *
     * @param string $className explicit class to use. If set, this
     *                          class or a subclass of it has to be used. If this is not possible,
     *                          an InvalidArgumentException has to be thrown.
     *
     * @return string $className if not null, the class configured for this
     *                node if defined and the Generic document if no better class can be
     *                found
     *
     * @throws ClassMismatchException if $node represents a class that is not
     *                                a descendant of $className
     */
    public function getClassName(DocumentManagerInterface $dm, NodeInterface $node, $className = null);

    /**
     * Write any relevant meta data into the node to be able to map back to a class name later.
     *
     * @param string $className
     */
    public function writeMetadata(DocumentManagerInterface $dm, NodeInterface $node, $className);

    /**
     * Check if the document is instance of the specified $className and
     * throw exception if not.
     *
     * @param object $document
     * @param string $className
     *
     * @throws ClassMismatchException if document is not of type $className
     */
    public function validateClassName(DocumentManagerInterface $dm, $document, $className);
}
