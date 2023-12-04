<?php

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\ODM\PHPCR\Exception\BadMethodCallException;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
use PHPCR\NodeInterface;

/**
 * This class represents a jcr nt:resource and is used by the File document.
 *
 * @see http://wiki.apache.org/jackrabbit/nt:resource
 */
#[PHPCR\Document(nodeType: 'nt:resource')]
class Resource
{   #[PHPCR\Id]
    protected string $id;

    /**
     * @var NodeInterface
     */
    #[PHPCR\Node]
    protected NodeInterface $node;

    #[PHPCR\Nodename]
    protected string $nodename;

    #[PHPCR\ParentDocument]
    protected object $parent;

    /**
     * @var resource
     */
    #[PHPCR\Field(property: 'jcr:data', type: 'binary')]
    protected $data;

    #[PHPCR\Field(property: 'jcr:mimeType', type: 'string')]
    protected string $mimeType = 'application/octet-stream';

    #[PHPCR\Field(property: 'jcr:encoding', type: 'string', nullable: true)]
    protected string $encoding;

    #[PHPCR\Field(property: 'jcr:lastModified', type: 'date')]
    protected \DateTimeInterface $lastModified;

    #[PHPCR\Field(property: 'jcr:lastModifiedBy', type: 'string')]
    protected string $lastModifiedBy;

    /**
     * The node name of the file.
     */
    public function getNodename(): string
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
     */
    public function setNodename(string $name): self
    {
        $this->nodename = $name;

        return $this;
    }

    /**
     * The parent File document of this Resource document.
     *
     * @return object file document that is the parent of this node
     */
    public function getParentDocument(): object
    {
        return $this->parent;
    }

    public function setParentDocument(object $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Set the data from a binary stream.

     * @param resource $data the contents of this resource
     */
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the binary data stream of this resource.
     *
     * @return resource
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
     * @return int the resource size in bytes
     */
    public function getSize(): int
    {
        if (!isset($this->node)) {
            throw new BadMethodCallException('Do not call Resource::getSize on unsaved objects, as it only reads the stored size.');
        }

        return $this->node->getProperty('jcr:data')->getLength();
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Set the encoding information for the data stream.
     */
    public function setEncoding(string $encoding): self
    {
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * Get the optional encoding information for the data stream.
     */
    public function getEncoding(): ?string
    {
        return $this->encoding;
    }

    /**
     * Set the last modified date manually.
     *
     * This might be updated automatically by some PHPCR implementations, but
     * it is not guaranteed by the specification.
     */
    public function setLastModified(\DateTimeInterface $lastModified): self
    {
        $this->lastModified = $lastModified;

        return $this;
    }

    public function getLastModified(): ?\DateTimeInterface
    {
        return $this->lastModified;
    }

    /**
     * Set the jcr username of the user that last modified this resource.
     *
     * This might be updated automatically by some PHPCR implementations, but
     * it is not guaranteed by the specification.
     */
    public function setLastModifiedBy(string $lastModifiedBy): self
    {
        $this->lastModifiedBy = $lastModifiedBy;

        return $this;
    }

    /**
     * Get the jcr username of the user that last modified this resource.
     */
    public function getLastModifiedBy(): ?string
    {
        return $this->lastModifiedBy;
    }

    /**
     * Get mime type and encoding (RFC2045).
     */
    public function getMime(): string
    {
        return $this->getMimeType().($this->getEncoding() ? '; charset='.$this->getEncoding() : '');
    }

    public function __toString(): string
    {
        return $this->nodename;
    }
}
