<?php

namespace Doctrine\ODM\PHPCR;

/**
 * Interface for objects that resolve to the node type nt:hierarchyNode, like
 * the File and Folder documents.
 *
 * @see http://wiki.apache.org/jackrabbit/nt:hierarchyNode
 */
interface HierarchyInterface
{
    /**
     * Get the parent document of this document, if set.
     */
    public function getParentDocument(): ?object;

    /**
     * Set the parent document for this document.
     */
    public function setParentDocument(object $parent): self;
}
