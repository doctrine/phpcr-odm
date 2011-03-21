<?php

namespace Doctrine\ODM\PHPCR\Document;

/**
  * @phpcr:Document(alias="file", nodeType="nt:file")
  */
class File 
{
    /** @phpcr:Path */
    protected $path;

    /** @phpcr:Node */
    protected $node;

    /** @phpcr:Date(name="jcr:created") */
    protected $created;

    /** @phpcr:String(name="jcr:createdBy") */
    protected $createdBy;

    /** @phpcr:Child(name="jcr:content") */
    protected $content;

    /**
     * Set the content for this file from the given filename.
     * Calls file_get_contents with the given filename
     *
     * @param string $filename name of the file which contents should be used
     */
    public function setContentFromFile($filename)
    {
        if ($this->content === null)
        {
            $this->content = new Resource();
        }
        $this->content->setData(file_get_contents($filename));
    } 

}
