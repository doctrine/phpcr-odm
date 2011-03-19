<?php

namespace Doctrine\ODM\PHPCR\Document;

/**
  * @phpcr:Document(alias="folder", nodeType="nt:folder")
  */
class Folder
{
    /** @phpcr:Path */
    protected $path;

    /** @phpcr:Node */
    protected $node;

    /** @phpcr:Date(name="jcr:created") */
    protected $created;

    /** @phpcr:String(name="jcr:createdBy") */
    protected $createdBy;
}


