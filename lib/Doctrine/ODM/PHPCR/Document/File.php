<?php

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * This class represents a JCR file, aka nt:file.
 * @ see http://wiki.apache.org/jackrabbit/nt:file
 *
 * @PHPCRODM\Document(nodeType="nt:file", referenceable=true)
 */
class File extends AbstractFile
{
    /** @PHPCRODM\Child(name="jcr:content") */
    protected $content;

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
            throw new \RuntimeException("File '$filename' not found");
        }

        $this->content->setData($stream);
        $this->content->setLastModified(new \DateTime('@'.filemtime($filename)));
        $this->content->setMimeType(mime_content_type($filename));
        //encoding???
    }

    /**
     * Set the content for this file from the given Resource.
     *
     * @param Resource $content
     */
    public function setContent(Resource $content)
    {
        $this->content = $content;
    }

    /**
     * Set the content for this file from the given resource or string.
     *
     * @param resource|string $content the content for the file
     */
    public function setFileContent($content)
    {
        $this->getContent();

        if (!is_resource($content)) {
            $stream = fopen('php://memory', 'rwb+');
            fwrite($stream, $content);
            rewind($stream);
        } else {
            $stream = $content;
        }

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
            $this->content->setLastModified(new \DateTime());
        }

        return $this->content;
    }
}
