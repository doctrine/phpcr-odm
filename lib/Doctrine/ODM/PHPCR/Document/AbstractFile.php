<?php

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\ODM\PHPCR\HierarchyInterface;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
use PHPCR\NodeInterface;

/**
 * This class represents an abstract "file".
 */
#[PHPCR\MappedSuperclass(mixins: ['mix:created'])]
abstract class AbstractFile implements HierarchyInterface
{
    #[PHPCR\Id(strategy: 'parent')]
    protected string $id;

    #[PHPCR\Node]
    protected NodeInterface $node;

    #[PHPCR\Nodename]
    protected string $nodename = '';

    #[PHPCR\ParentDocument]
    protected ?object $parent;

    #[PHPCR\Field(property: 'jcr:created', type: 'date')]
    protected ?\DateTimeInterface $created = null;

    #[PHPCR\Field(property: 'jcr:createdBy', type: 'string')]
    protected ?string $createdBy = null;

    /**
     *  Set the id (the PHPCR path).
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
