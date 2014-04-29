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

use Doctrine\ODM\PHPCR\Exception\BadMethodCallException;
use PHPCR\NodeInterface;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * This class represents a jcr nt:resource and is used by the File document
 *
 * @see http://wiki.apache.org/jackrabbit/nt:resource
 *
 * @PHPCRODM\Document(nodeType="nt:resource")
 */
class Resource
{
    /**
     * @PHPCRODM\Id
     */
    protected $id;

    /**
     * @var NodeInterface
     *
     * @PHPCRODM\Node
     */
    protected $node;

    /**
     * @PHPCRODM\Nodename
     */
    protected $nodename;

    /**
     * @PHPCRODM\ParentDocument
     */
    protected $parent;

    /**
     * @PHPCRODM\Binary(property="jcr:data")
     */
    protected $data;

    /**
     * @PHPCRODM\String(property="jcr:mimeType")
     */
    protected $mimeType = 'application/octet-stream';

    /**
     * @PHPCRODM\String(property="jcr:encoding", nullable=true)
     */
    protected $encoding;

    /**
     * @PHPCRODM\Date(property="jcr:lastModified")
     */
    protected $lastModified;

    /**
     * @PHPCRODM\String(property="jcr:lastModifiedBy")
     */
    protected $lastModifiedBy;

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
     * Set the node name of the resource.
     *
     * Only mutable on new document before the persist. For an nt:file resource
     * child, this must be "jcr:content".
     *
     * @param string $name the name of the resource
     *
     * @return $this
     */
    public function setNodename($name)
    {
        $this->nodename = $name;

        return $this;
    }

    /**
     * The parent File document of this Resource document.
     *
     * @return object File document that is the parent of this node.
     */
    public function getParentDocument()
    {
        return $this->parent;
    }

    /**
     * Kept for BC
     *
     * @deprecated use getParentDocument instead.
     */
    public function getParent()
    {
        return $this->getParentDocument();
    }

    /**
     * Set the parent document of this resource.
     *
     * @param object $parent Document that is the parent of this node.
     *
     * @return $this
     */
    public function setParentDocument($parent)
    {
        $this->parent = $parent;

        return $this;
    }


    /**
     * Kept for BC
     *
     * @deprecated use setParentDocument instead.
     */
    public function setParent($parent)
    {
        return $this->setParentDocument($parent);
    }

    /**
     * Set the data from a binary stream.
     *
     * @param stream $data the contents of this resource
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the binary data stream of this resource.
     *
     * @param stream
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the size of the <strong>stored</strong> data stream in this
     * resource.
     *
     * You should call this method instead of anything else to know the file
     * size as PHPCR implementations are expected to be able to provide this
     * information without needing to to load the actual data stream.
     *
     * Do not use this right after updating data before flushing, as it will
     * only look at the stored data.
     *
     * @return int the resource size in bytes.
     */
    public function getSize()
    {
        if (null === $this->node) {
            throw new BadMethodCallException('Do not call Resource::getSize on unsaved objects, as it only reads the stored size.');
        }

        return $this->node->getProperty('jcr:data')->getLength();
    }

    /**
     * Set the mime type information for this resource.
     *
     * @param string $mimeType
     *
     * @return $this
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get the mime type information of this resource.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Set the encoding information for the data stream.
     *
     * @param string $encoding
     *
     * @return $this
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * Get the optional encoding information for the data stream.
     *
     * @return string|null the encoding of this resource
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Set the last modified date manually.
     *
     * This might be updated automatically by some PHPCR implementations, but
     * it is not required by the specification.
     *
     * @param \DateTime $lastModified
     *
     * @return $this
     */
    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;

        return $this;
    }

    /**
     * Get the last modified date.
     *
     * @return \DateTime
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * Set the jcr username of the user that last modified this resource.
     *
     * This might be updated automatically by some PHPCR implementations, but
     * it is not required by the specification.
     *
     * @param string $lastModifiedBy
     *
     * @return $this
     */
    public function setLastModifiedBy($lastModifiedBy)
    {
        $this->lastModifiedBy = $lastModifiedBy;

        return $this;
    }

    /**
     * Get the jcr username of the user that last modified this resource.
     *
     * @return string
     */
    public function getLastModifiedBy()
    {
        return $this->lastModifiedBy;
    }

    /**
     * Get mime type and encoding (RFC2045)
     *
     * @return string
     */
    public function getMime()
    {
        return $this->getMimeType() . ($this->getEncoding() ? '; charset=' . $this->getEncoding() : '');
    }

    /**
     * String representation
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->nodename;
    }
}
