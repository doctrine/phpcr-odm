<?php

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * This class represents an arbitrary node
 *
 * It is used as a default document, for example with the ParentDocument annotation.
 * You can not use this to create nodes as it has no type annotation.
 *
 * @PHPCRODM\Document(alias="odm_generic")
 */
class Generic
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

    /**
     * Id (path) of this document
     *
     * @return string the id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * The node name of the document.
     *
     * @return string
     */
    public function getNodename()
    {
        return $this->nodename;
    }

    /**
     * The parent document of this document.
     *
     * If there is information on the document type, the document is of the
     * specified type, otherwise it will be a Generic document
     *
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * The children documents of this document
     *
     * If there is information on the document type, the documents are of the
     * specified type, otherwise they will be Generic documents
     *
     * @return string
     */
    public function getChildren()
    {
        return $this->children;
    }
}
