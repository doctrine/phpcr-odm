<?php

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * This class represents a Folder in the repository, aka nt:folder
 * @see http://wiki.apache.org/jackrabbit/nt:folder
 *
 * To add files or folders to a folder, create the new File/Folder and set
 * this document as parent, then persist the new File/Folder.
 *
 * @PHPCRODM\Document(nodeType="nt:folder")
 */
class Folder extends AbstractFile
{
    /** @PHPCRODM\Children() */
    protected $children;

    /** @PHPCRODM\Child() */
    protected $child;

    /**
     * The children File documents of this Folder document
     *
     * @return list of File documents
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Sets the children of this Folder document
     *
     * @param $children ArrayCollection
     */
    public function setChildren(ArrayCollection $children)
    {
        $this->children = $children;
    }

    /**
     * Add a child File to this Folder document
     *
     * @param $child AbstractFile
     */
    public function addChild(AbstractFile $child)
    {
        if (null === $this->children) {
            $this->children = new ArrayCollection();
        }

        $this->children->add($child);
    }
}
