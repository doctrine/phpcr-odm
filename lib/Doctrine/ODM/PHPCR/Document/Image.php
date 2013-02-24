<?php

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class Image
{

    /**
     * @PHPCRODM\Id
     */
    protected $path;

    /**
     * Image file child
     *
     * @PHPCRODM\Child(name="file", cascade="persist")
     */
    protected $file;


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
        return $this->path;
    }

}
