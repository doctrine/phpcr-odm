<?php

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * This class represents a JCR file, aka nt:file.
 * @ see http://wiki.apache.org/jackrabbit/nt:file
 *
 * @PHPCRODM\Document(alias="file", nodeType="nt:file")
 */
class File
{
    /** @PHPCRODM\Id */
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

    /** @PHPCRODM\Child(name="jcr:content") */
    protected $content;

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

    /**
     * Set the content for this file from the given filename.
     * Calls file_get_contents with the given filename
     *
     * @param string $filename name of the file which contents should be used
     */
    public function setFileContentFromFilesystem($filename)
    {
        $this->getContent();
        $stream = fopen($filename, 'rb');
        if (! $stream) {
            throw new \Exception("File $filename not found");
        }

        $this->content->setData($stream);
        $this->content->setLastModified(new \DateTime('@'.filemtime($filename)));
        $this->content->setMimeType(mime_content_type($filename));
        //encoding???
    }

    /**
     * Set the content for this file from the given string.
     *
     * @param string $content the content for the file
     */
    public function setFileContent($content)
    {
        $this->getContent();
        $stream = fopen('php://memory', 'rwb+');
        fwrite($stream, $content);
        rewind($stream);
        $this->content->setData($stream);
    }

    /**
     * Get a stream for the content of this file.
     *
     * @return stream the content for the file
     */
    public function getFileContentAsStream()
    {
      return $this->getContent()->getData();
    }

    /**
     * Get the content for this file as string.
     *
     * @return string the content for the file in a string
     */
    public function getFileContent()
    {
      $content = stream_get_contents($this->getContent()->getData());
      return $content !== false ? $content : '';
    }

    /*
     * Ensure content object is created
     */
    private function getContent()
    {
        if ($this->content === null) {
            $this->content = new Resource();
        }
        return $this->content;
    }
}
