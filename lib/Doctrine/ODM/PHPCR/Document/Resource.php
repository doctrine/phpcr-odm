<?php

namespace Doctrine\ODM\PHPCR\Document;

/**
 * This class represents a jcr nt:resource and is used by the File document
 * @see http://wiki.apache.org/jackrabbit/nt:resource
 *
 * @phpcr:Document(alias="resource", nodeType="nt:resource")
 */
class Resource
{
    /** @phpcr:Path */
    protected $path;

    /** @phpcr:Node */
    protected $node;

    /** @phpcr:Binary(name="jcr:data") */
    protected $data;

    /** @phpcr:String(name="jcr:mimeType") */
    protected $mimeType;

    /** @phpcr:String(name="jcr:encoding") */
    protected $encoding;

    /** @phpcr:Date(name="jcr:lastModified") */
    protected $lastModified;

    /** @phpcr:String(name="jcr:lastModifiedBy") */
    protected $lastModifiedBy;

    /**
     * setter for the data property
     * This property stores the content of this resource
     *  
     * @param string $data the contents of this resource 
     */
    public function setData($data)
    {
        $this->data = $data;
    }
  
    /**
     * getter for the data property
     * This returns the content of this resource
     *  
     * @param string 
     */
    public function getData()
    {
        return $this->data;
    }
  
    /**
     * setter for the mimeType property
     * This property stores the mimeType of this resource
     *  
     * @param string $mimeType 
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
    }

    /**
     * getter for the mimeType property
     * This returns the mimeType of this resource
     *  
     * @return string 
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * setter for the encoding property
     * This property stores the encoding of this resource
     *  
     * @param string $encoding 
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    /**
     * getter for the encoding property
     * This returns the encoding of this resource
     *  
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * setter for the lastModified property
     * This property stores the lastModified date of this resource
     * If not set, this might be set by PHPCR
     * 
     * @param DateTime $lastModified 
     */
    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;
    }

    /**
     * getter for the lastModified property
     * This returns the lastModified date of this resource
     * 
     * @return DateTime 
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * setter for the lastModifiedBy property
     * name of the jcr user that last modified this resource
     * 
     * @param string $lastModifiedBy
     */
    public function setLastModifiedBy($lastModifiedBy)
    {
        $this->lastModifiedBy = $lastModifiedBy;
    }

    /**
     * getter for the lastModifiedBy property
     * This returns name of the jcr user that last modified this resource
     * 
     * @return string
     */
    public function getLastModifiedBy()
    {
        return $this->lastModifiedBy;
    }
}
