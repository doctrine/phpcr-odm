<?php

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class Image
{
    /**
     * The PHPCR path
     *
     * @PHPCRODM\Id(strategy="parent")
     */
    protected $id;

    /**
     * @PHPCRODM\ParentDocument
     */
    protected $parent;

    /**
     * @PHPCRODM\Nodename
     */
    protected $nodename;

    /**
     * Image file child
     *
     * @PHPCRODM\Child(nodeName="file", cascade="persist")
     *
     * @var File
     */
    protected $file;

    /**
     * Set the id (PHPCR path) of this image document
     *
     * Only makes sense before persisting a new document. Note that the
     * preferred way to define the id is by setting the parent and the node
     * name rather than the absolute id.
     *
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the id (PHPCR path) of this image document
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param object $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return object
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set the node name for this image
     *
     * @param string $nodename
     */
    public function setNodename($nodename)
    {
        $this->nodename = $nodename;
    }

    /**
     * Get the node name of this image
     *
     * @return string
     */
    public function getNodename()
    {
        return $this->nodename;
    }

    /**
     * @param $file File
     */
    public function setFile(File $file)
    {
        $this->file = $file;
    }

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param $mimeType string
     */
    public function setMimeType($mimeType)
    {
        $this->file->getContent()->setMimeType($mimeType);
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->file->getContent()->getMimeType();
    }

    /**
     * @return stream
     */
    public function getContent()
    {
        return $this->file->getFileContentAsStream();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->id;
    }

}
