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

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use PHPCR\NodeInterface;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;

/**
 * Property collection class
 *
 * This class stores all documents or their proxies referenced by a reference many property
 */
class ReferenceManyCollection extends PersistentCollection
{
    private $document;
    private $property;
    private $referencedNodes;
    private $targetDocument;
    private $originalReferencePaths;

    /**
     * Creates a new persistent collection.
     *
     * @param DocumentManager $dm              The DocumentManager the collection will be associated with.
     * @param object          $document        The document with the references property
     * @param string          $property        The node property name with the multivalued references
     * @param array           $referencedNodes An array of referenced nodes (UUID or path)
     * @param string          $targetDocument  The class name of the target documents
     * @param string          $locale          The locale to use during the loading of this collection
     */
    public function __construct(DocumentManager $dm, $document, $property, array $referencedNodes, $targetDocument, $locale = null)
    {
        $this->dm = $dm;
        $this->document = $document;
        $this->property = $property;
        $this->referencedNodes = $referencedNodes;
        $this->targetDocument = $targetDocument;
        $this->locale = $locale;
    }

    /**
     * @param DocumentManager  $dm              The DocumentManager the collection will be associated with.
     * @param object           $document        The document with the references property
     * @param string           $property        The node property name with the multivalued references
     * @param array|Collection $collection      The collection to initialize with
     * @param string           $targetDocument  The class name of the target documents
     * @param bool             $forceOverwrite If to force the database to be forced to the state of the collection
     *
     * @return ReferenceManyCollection
     */
    public static function createFromCollection(DocumentManager $dm, $document, $property, $collection, $targetDocument, $forceOverwrite = false)
    {
        $referenceCollection = new self($dm, $document, $property, array(), $targetDocument);
        $referenceCollection->initializeFromCollection($collection, $forceOverwrite);

        return $referenceCollection;
    }

    /** {@inheritDoc} */
    public function refresh()
    {
        try {
            $property = $this->dm->getNodeForDocument($this->document)->getProperty($this->property);
            $this->referencedNodes = $property->getString();
        } catch (InvalidArgumentException $e) {
            $this->referencedNodes = array();
        }

        parent::refresh();
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize()
    {
        if (!$this->isInitialized()) {
            $referencedDocs = array();
            $referencedNodes = $this->dm->getPhpcrSession()->getNodesByIdentifier($this->referencedNodes);
            $uow = $this->dm->getUnitOfWork();
            $uow->getPrefetchHelper()->prefetch($this->dm, $referencedNodes, $this->locale);

            $this->originalReferencePaths = array();
            foreach ($referencedNodes as $referencedNode) {
                $proxy = $uow->getOrCreateProxyFromNode($referencedNode, $this->locale);
                if (isset($targetDocument) && !$proxy instanceof $this->targetDocument) {
                    throw new PHPCRException("Unexpected class for referenced document at '{$referencedNode->getPath()}'. Expected '{$this->targetDocument}' but got '".ClassUtils::getClass($proxy)."'.");
                }
                $referencedDocs[] = $proxy;
                $this->originalReferencePaths[] = $referencedNode->getPath();
            }

            $this->collection = new ArrayCollection($referencedDocs);
            $this->initialized = self::INITIALIZED_FROM_PHPCR;
        }
    }

    /** {@inheritDoc} */
    public function count()
    {
        if (!$this->isInitialized()) {
            return count($this->referencedNodes);
        }

        return parent::count();
    }

    /** {@inheritDoc} */
    public function isEmpty()
    {
        if (!$this->isInitialized()) {
            return !$this->count();
        }

        return parent::isEmpty();
    }

    /**
     * Return the ordered list of references that existed when the collection was initialized
     *
     * @return array
     */
    public function getOriginalPaths()
    {
        if (null === $this->originalReferencePaths) {
            $this->originalReferencePaths = array();
            if (self::INITIALIZED_FROM_COLLECTION === $this->initialized) {
                $uow = $this->dm->getUnitOfWork();
                foreach ($this->collection as $reference) {
                    $this->originalReferencePaths[] = $uow->getDocumentId($reference);
                }
            } else {
                $nodes = $this->dm->getPhpcrSession()->getNodesByIdentifier($this->referencedNodes);
                foreach ($nodes as $node) {
                    $this->originalReferencePaths[] = $node->getPath();
                }
            }
        }

        return $this->originalReferencePaths;
    }

    /**
     * Reset original reference paths and mark the collection as non dirty
     */
    public function takeSnapshot()
    {
        if (is_array($this->originalReferencePaths)) {
            if ($this->isInitialized()) {
                foreach ($this->collection->toArray() as $document) {
                    try {
                        $this->originalReferencePaths[] = $this->dm->getUnitOfWork()->getDocumentId($document);
                    } catch (PHPCRException $e) {
                    }
                }
            } else {
                $this->originalReferencePaths = null;
            }
        }

        parent::takeSnapshot();
    }
}
