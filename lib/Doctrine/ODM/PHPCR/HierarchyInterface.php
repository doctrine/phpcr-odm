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
     * Get the parent document.
     *
     * @deprecated in favor of getParentDocument to avoid clashes with domain model parents.
     *
     * @return object|null
     */
    public function getParent();

    /**
     * Set the parent document for this document.
     *
     * @param object $parent
     *
     * @return $this
     */
    public function setParentDocument($parent);

    /**
     * Set the parent document.
     *
     * @deprecated in favor of getParentDocument to avoid clashes with domain model parents.
     *
     * @param object $parent
     *
     * @return $this
     */
    public function setParent($parent);
}
