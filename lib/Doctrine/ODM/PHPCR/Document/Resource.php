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


    public function setFileData($data)
    {
        $this->data = $data;
    }
}
    
