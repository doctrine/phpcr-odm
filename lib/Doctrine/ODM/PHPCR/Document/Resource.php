<?php

namespace Doctrine\ODM\PHPCR\Document;

/**
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


    public function setData($data)
    {
        $this->data = $data;
    }
  
    public function getData()
    {
        return $this->data;
    }
  
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;
    }

    public function getLastModified()
    {
        return $this->lastModified;
    }

    public function setLastModifiedBy($lastModifiedBy)
    {
        $this->lastModifiedBy = $lastModifiedBy;
    }

    public function getLastModifiedBy()
    {
        return $this->lastModifiedBy;
    }

}
