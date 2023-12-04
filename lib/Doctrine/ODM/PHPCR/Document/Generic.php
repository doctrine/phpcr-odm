<?php

namespace Doctrine\ODM\PHPCR\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\PHPCR\Exception\BadMethodCallException;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
use PHPCR\NodeInterface;

/**
 * This class represents an arbitrary node.
 *
 * It is used as a default document, for example with the ParentDocument annotation.
 * You can not use this to create nodes as it has no type annotation.
 */
#[PHPCR\Document]
class Generic
{
    /**
     * Id (path) of this document.
     */
    #[PHPCR\Id(strategy: 'parent')]
    protected string $id;

    #[PHPCR\Node]
    protected NodeInterface $node;

    #[PHPCR\Nodename]
    protected string $nodename = '';

    #[PHPCR\ParentDocument]
    protected object $parent;

    /**
     * @var Collection<object>
     */
    #[PHPCR\Children]
    protected Collection $children;

    /**
     * @var Collection<object>
     */
    #[PHPCR\MixedReferrers]
    protected Collection $referrers;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->referrers = new ArrayCollection();
    }

    /**
     * Id (path) of this document
     */
    public function getId(): string
    {
        if (!isset($this->id)) {
            throw new BadMethodCallException('Do not call getId on unsaved objects.');
        }
        return $this->id;
    }

    /**
     * The node of for document.
     */
    public function getNode(): NodeInterface
    {
        return $this->node;
    }

    /**
     * The node name of the document.
     */
    public function getNodename(): string
    {
        return $this->nodename;
    }

    /**
     * Set the node name of the document. (only mutable on new document before the persist).
     */
    public function setNodename(string $name): self
    {
        $this->nodename = $name;

        return $this;
    }

    /**
     * The parent document of this document.
     */
    public function getParentDocument(): object
    {
        if (!isset($this->parent)) {
            throw new BadMethodCallException('Do not call getParentDocument on unsaved objects before setting the parent.');
        }
        return $this->parent;
    }

    /**
     * Set the parent document of this document.
     */
    public function setParentDocument(object $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * The children documents of this document.
     *
     * If there is information on the document type, the documents are of the
     * specified type, otherwise they will be Generic documents
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function setChildren(Collection $children): self
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Add a child to this document.
     */
    public function addChild(object $child): self
    {
        $this->children->add($child);

        return $this;
    }

    /**
     * The documents having a reference to this document.
     *
     * If there is information on the document type, the documents are of the
     * specified type, otherwise they will be Generic documents
     */
    public function getReferrers(): Collection
    {
        return $this->referrers;
    }

    public function setReferrers(Collection $referrers): self
    {
        $this->referrers = $referrers;

        return $this;
    }

    public function addReferrer(object $referrer): self
    {
        $this->referrers->add($referrer);

        return $this;
    }

    public function __toString(): string
    {
        return $this->nodename;
    }
}
