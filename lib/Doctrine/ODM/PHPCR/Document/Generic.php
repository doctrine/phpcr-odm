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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
use PHPCR\NodeInterface;

/**
 * This class represents an arbitrary node
 *
 * It is used as a default document, for example with the ParentDocument annotation.
 * You can not use this to create nodes as it has no type annotation.
 */
#[PHPCR\Document]
class Generic
{
    #[PHPCR\Id(strategy: 'parent')]
    protected $id;

    #[PHPCR\Node]
    protected $node;

    #[PHPCR\Nodename]
    protected $nodename;

    #[PHPCR\ParentDocument]
    protected $parent;

    /**
     * @var Collection
     */
    #[PHPCR\Children]
    protected $children;

    /**
     * @var Collection
     */
    #[PHPCR\MixedReferrers]
    protected $referrers;

    /**
     * Id (path) of this document
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * The node of for document.
     *
     * @return NodeInterface
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * The node name of the document.
     *
     * @return string
     */
    public function getNodename()
    {
        return $this->nodename;
    }

    /**
     * Set the node name of the document. (only mutable on new document before the persist)
     *
     * @param string $name the name of the document
     *
     * @return self
     */
    public function setNodename($name)
    {
        $this->nodename = $name;

        return $this;
    }

    /**
     * The parent document of this document.
     *
     * @return object folder document that is the parent of this node
     */
    public function getParentDocument()
    {
        return $this->parent;
    }

    /**
     * Kept for BC
     *
     * @deprecated use getParentDocument instead
     */
    public function getParent()
    {
        return $this->getParentDocument();
    }

    /**
     * Set the parent document of this document.
     *
     * @param object $parent Document that is the parent of this node..
     *
     * @return self
     */
    public function setParentDocument($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Kept for BC
     *
     * @deprecated use setParentDocument instead
     */
    public function setParent($parent)
    {
        return $this->setParentDocument($parent);
    }

    /**
     * The children documents of this document
     *
     * If there is information on the document type, the documents are of the
     * specified type, otherwise they will be Generic documents
     *
     * @return Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Sets the children
     *
     * @param $children ArrayCollection
     *
     * @return self
     */
    public function setChildren(ArrayCollection $children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Add a child to this document
     *
     * @param $child
     *
     * @return self
     */
    public function addChild($child)
    {
        if (null === $this->children) {
            $this->children = new ArrayCollection();
        }

        $this->children->add($child);

        return $this;
    }

    /**
     * The documents having a reference to this document
     *
     * If there is information on the document type, the documents are of the
     * specified type, otherwise they will be Generic documents
     *
     * @return Collection
     */
    public function getReferrers()
    {
        return $this->referrers;
    }

    /**
     * Sets the referrers
     *
     * @param $referrers ArrayCollection
     *
     * @return self;
     */
    public function setReferrers(ArrayCollection $referrers)
    {
        $this->referrers = $referrers;

        return $this;
    }

    /**
     * Add a referrer to this document
     *
     * @param $referrer
     *
     * @return self;
     */
    public function addReferrer($referrer)
    {
        if (null === $this->referrers) {
            $this->referrers = new ArrayCollection();
        }

        $this->referrers->add($referrer);

        return $this;
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
