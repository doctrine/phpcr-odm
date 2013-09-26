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
     * Get the parent node.
     *
     * @return Object|null
     */
    public function getParent();

    /**
     * Set the parent node.
     *
     * @param Object $parent
     *
     * @return boolean
     */
    public function setParent($parent);
}
