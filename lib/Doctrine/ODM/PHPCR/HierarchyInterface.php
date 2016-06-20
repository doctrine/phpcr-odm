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
     * Get the parent document of this document.
     *
     * @return object|null
     */
    public function getParentDocument();

    /**
     * Set the parent document for this document.
     *
     * @param object $parent
     *
     * @return $this
     */
    public function setParentDocument($parent);
}
