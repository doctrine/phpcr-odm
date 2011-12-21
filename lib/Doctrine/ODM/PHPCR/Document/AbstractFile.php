<?php

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * This class represents an abstract "file"
 */
abstract class AbstractFile
{
    /** @PHPCRODM\Id(strategy="parent") */
    protected $id;

    /** @PHPCRODM\Node */
    protected $node;

    /** @PHPCRODM\Nodename */
    protected $nodename;

    /** @PHPCRODM\ParentDocument */
    protected $parent;

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
     * The parent Folder document of this document.
     *
     * @param object $parent Folder document that is the parent of this node.
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set the parent document of this document. Only mutable on new document
     * before the persist.
     *
     * @param object $parent Document that is the parent of this node. Must be
     *      a Folder or otherwise resolve to nt:folder
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
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
