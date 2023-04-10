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
     * @PHPCRODM\Children(cascade="all")
     */
    protected Collection $children;

    /**
     * @PHPCRODM\Child(cascade="all")
     */
    protected AbstractFile $child;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * Files and folders inside this folder.
     *
     * @return Collection<HierarchyInterface>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * Sets the children of this Folder document.
     *
     * Children must be 'nt:hierarchyNode', typically extending AbstractFile (Folder or File).
     *
     * @param Collection<HierarchyInterface> $children
     */
    public function setChildren(Collection $children): self
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Add a child document that resolves to nt:hierarchyNode (like the File or Folder).
     */
    public function addChild(HierarchyInterface $child): self
    {
        $this->children->add($child);

        return $this;
    }
}
