<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\ODM\PHPCR\Exception\RuntimeException;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * This class represents a JCR file, aka nt:file.
 * @see http://wiki.apache.org/jackrabbit/nt:file
 *
 * @PHPCRODM\Document(nodeType="nt:file", mixins={}, referenceable=true)
 */
class File extends AbstractFile
{
    /**
     * @var Resource
     * @PHPCRODM\Child(nodeName="jcr:content", cascade="all")
     */
    protected $content;

    /**
     * Set the content for this file from the given filename.
     * Calls file_get_contents with the given filename
     *
     * @param string $filename name of the file which contents should be used
     *
     * @return $this
     *
     * @throws RuntimeException If the filename does not point to a file that can be read.
     */
    public function setFileContentFromFilesystem($filename)
    {
        if (!$filename) {
            throw new RuntimeException('The filename may not be empty');
        }
        if (!is_readable($filename)) {
            throw new RuntimeException(sprintf('File "%s" not found or not readable', $filename));
        }
        $this->getContent();
        $stream = fopen($filename, 'rb');
        if (! $stream) {
            throw new RuntimeException(sprintf('Failed to open file "%s"', $filename));
        }

        $this->content->setData($stream);
        $this->content->setLastModified(new \DateTime('@'.filemtime($filename)));

        $finfo = new \finfo();
        $this->content->setMimeType($finfo->file($filename,FILEINFO_MIME_TYPE));
        $this->content->setEncoding($finfo->file($filename,FILEINFO_MIME_ENCODING));

        return $this;
    }

    /**
     * Set the content for this file from the given Resource.
     *
     * @param Resource $content
     *
     * @return $this
     */
    public function setContent(Resource $content)
    {
        $this->content = $content;

        return $this;
    }

    /*
     * Get the resource representing the data of this file.
     *
     * Ensures the content object is created
     *
     * @return Resource
     */
    public function getContent()
    {
        if ($this->content === null) {
            $this->content = new Resource();
            $this->content->setLastModified(new \DateTime());
        }

        return $this->content;
    }

    /**
     * Set the content for this file from the given resource or string.
     *
     * @param resource|string $content the content for the file
     *
     * @return $this
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

        return $this;
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
}
