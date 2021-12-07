<?php

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\PHPCR\HierarchyInterface;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * This class represents a Folder in the repository, aka nt:folder.
 *
 * @see http://wiki.apache.org/jackrabbit/nt:folder
 *
 * To add files or folders to a folder, create the new File/Folder and set
 * this document as parent, then persist the new File/Folder.
 *
 * @PHPCRODM\Document(nodeType="nt:folder", mixins={})
 */
class Folder extends AbstractFile
{
    /**
     * @var ArrayCollection
     * @PHPCRODM\Children(cascade="all")
     */
    protected $children;

    /**
     * @var AbstractFile
     * @PHPCRODM\Child(cascade="all")
     */
    protected $child;

    /**
     * The children File documents of this Folder document.
     *
     * @return Collection list of File documents
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Sets the children of this Folder document.
     *
     * @param $children ArrayCollection
     *
     * @return $this
     */
    public function setChildren(ArrayCollection $children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Add a child document that resolves to nt:hierarchyNode (like the File)
     * to this document that resolves to nt:folder (like the Folder).
     *
     * @param $child HierarchyInterface
     *
     * @return $this
     */
    public function addChild(HierarchyInterface $child)
    {
        if (null === $this->children) {
            $this->children = new ArrayCollection();
        }

        $this->children->add($child);

        return $this;
    }
}
