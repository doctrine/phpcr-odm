<?php

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * This class represents a Folder in the repository, aka nt:folder
 * @see http://wiki.apache.org/jackrabbit/nt:folder
 *
 * To add files or folders to a folder, create the new File/Folder and set
 * this document as parent, then persist the new File/Folder.
 *
 * @PHPCRODM\Document(alias="folder", nodeType="nt:folder")
 */
class Folder
{
    /** @PHPCRODM\Id */
    protected $id;

    /** @PHPCRODM\Node */
    protected $node;

    /** @PHPCRODM\Nodename */
    protected $nodename;

    /** @PHPCRODM\ParentDocument */
    protected $parent;

    /** @PHPCRODM\Children */
    protected $children;

    /** @PHPCRODM\Date(name="jcr:created") */
    protected $created;

    /** @PHPCRODM\String(name="jcr:createdBy") */
    protected $createdBy;

    /**
     * setter for id
     *
     * @param string $id of the node
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * getter for id
     *
     * @return string id of the node
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * The node name of the file.
     *
     * @return string
     */
    public function getNodename()
    {
        return $this->nodename;
    }

    /**
     * Set the node name of the file. (only mutable on new document before the persist)
     *
     * @param string $name the name of the file
     */
    public function setNodename($name)
    {
        $this->nodename = $name;
    }

    /**
     * The parent document of this document.
     *
     * If there is information on the document type, the document is of the
     * specified type, otherwise it will be a Generic document
     *
     * @param object $parent Document that is the parent of this node.
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set the parent document of this document. Only mutable on new document
     * before the persist.
     *
     * @param object $parent Document that is the parent of this node.
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

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
     * getter for created
     * The created date is assigned by the content repository
     *
     * @return DateTime created date of the file
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * getter for createdBy
     * The createdBy is assigned by the content repository
     * This is the name of the (jcr) user that created the node
     *
     * @return string name of the (jcr) user who created the file
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }
}
