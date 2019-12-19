<?php

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\ODM\PHPCR\HierarchyInterface;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * This class represents an abstract "file".
 *
 * @PHPCRODM\MappedSuperclass(mixins="mix:created")
 */
abstract class AbstractFile implements HierarchyInterface
{
    /** @PHPCRODM\Id(strategy="parent") */
    protected $id;

    /** @PHPCRODM\Node */
    protected $node;

    /** @PHPCRODM\Nodename */
    protected $nodename;

    /** @PHPCRODM\ParentDocument */
    protected $parent;

    /** @PHPCRODM\Field(type="date", property="jcr:created") */
    protected $created;

    /** @PHPCRODM\Field(type="string", property="jcr:createdBy") */
    protected $createdBy;

    /**
     * Set the id (the PHPCR path).
     *
     * @param string $id of the node
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get for id (the PHPCR path).
     *
     * @return string id of the document
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
     * Set the node name of the file. (only mutable on new document before the persist).
     *
     * @param string $name the name of the file
     *
     * @return $this
     */
    public function setNodename($name)
    {
        $this->nodename = $name;

        return $this;
    }

    /**
     * The parent document of this document. Could be a Folder.
     *
     * @return object document that is the parent of this node
     */
    public function getParentDocument()
    {
        return $this->parent;
    }

    /**
     * Set the parent document of this document.
     *
     * @param object $parent Document that is the parent of this node. Could be
     *                       a Folder or otherwise resolve to nt:folder
     *
     * @return $this
     */
    public function setParentDocument($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * getter for created
     * The created date is assigned by the content repository.
     *
     * @return \DateTime created date of the file
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * getter for createdBy
     * The createdBy is assigned by the content repository
     * This is the name of the (jcr) user that created the node.
     *
     * @return string name of the (jcr) user who created the file
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * String representation.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->nodename;
    }
}
