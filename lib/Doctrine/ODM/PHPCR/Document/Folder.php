<?php

namespace Doctrine\ODM\PHPCR\Document;

/**
 * This class represents a Folder in the repository, aka nt:folder
 * @see http://wiki.apache.org/jackrabbit/nt:folder
 *
 * @phpcr:Document(alias="folder", nodeType="nt:folder")
 */
class Folder
{
    /** @phpcr:Id */
    protected $id;

    /** @phpcr:Node */
    protected $node;

    /** @phpcr:Date(name="jcr:created") */
    protected $created;

    /** @phpcr:String(name="jcr:createdBy") */
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
