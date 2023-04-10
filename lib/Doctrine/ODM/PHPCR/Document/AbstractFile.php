<?php

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\ODM\PHPCR\HierarchyInterface;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use PHPCR\NodeInterface;

/**
 * This class represents an abstract "file".
 *
 * @PHPCRODM\MappedSuperclass(mixins="mix:created")
 */
abstract class AbstractFile implements HierarchyInterface
{
    /**
     * @PHPCRODM\Id(strategy="parent")
     */
    protected string $id;

    /**
     * @PHPCRODM\Node
     */
    protected NodeInterface $node;

    /**
     * @PHPCRODM\Nodename
     */
    protected string $nodename = '';

    /**
     * @PHPCRODM\ParentDocument
     */
    protected ?object $parent;

    /**
     * @PHPCRODM\Field(type="date", property="jcr:created")
     */
    protected ?\DateTimeInterface $created = null;

    /**
     * @PHPCRODM\Field(type="string", property="jcr:createdBy")
     */
    protected ?string $createdBy = null;

    /**
     * Set the id (the PHPCR path).
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get for id (the PHPCR path).
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * The node name of the file.
     */
    public function getNodename(): string
    {
        return $this->nodename;
    }

    /**
     * Set the node name of the file. (only mutable on new document before the persist).
     */
    public function setNodename(string $name): self
    {
        $this->nodename = $name;

        return $this;
    }

    /**
     * The parent document of this document. Could be a Folder.
     */
    public function getParentDocument(): ?object
    {
        return $this->parent;
    }

    /**
     * Set the parent document of this document.
     *
     * @param object $parent Document that is the parent of this node. Could be
     *                       a Folder or otherwise resolve to nt:folder
     */
    public function setParentDocument(object $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * The created date is assigned by the content repository.
     */
    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    /**
     * The createdBy is assigned by the content repository
     * This is the name of the (jcr) user that created the node.
     */
    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function __toString(): string
    {
        return $this->nodename;
    }
}
