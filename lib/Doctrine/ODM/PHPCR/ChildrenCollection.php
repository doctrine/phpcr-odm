<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPCR\NodeInterface;
use PHPCR\Util\PathHelper;

/**
 * Children collection class.
 *
 * This class represents a collection of children of a document which phpcr
 * names match a optional filter
 */
class ChildrenCollection extends PersistentCollection
{
    private object $document;

    /**
     * @var array|string|null
     */
    private $filter;

    private int $fetchDepth;

    /**
     * @var string[]
     */
    private array $originalNodeNames;

    private NodeInterface $node;

    /**
     * Creates a new persistent collection.
     *
     * @param object       $document   The parent document instance
     * @param string|array $filter     Filter string or array of filter string
     * @param int          $fetchDepth Optional fetch depth, -1 to not override
     * @param string|null  $locale     The locale to use during the loading of this collection
     */
    public function __construct(DocumentManagerInterface $dm, object $document, $filter = null, int $fetchDepth = -1, ?string $locale = null)
    {
        $this->dm = $dm;
        $this->document = $document;
        $this->filter = $filter;
        $this->fetchDepth = $fetchDepth;
        $this->locale = $locale;
    }

    /**
     * @param array|Collection $collection The collection to initialize with
     * @param string|array     $filter     Filter string or array of filter string
     */
    public static function createFromCollection(DocumentManagerInterface $dm, object $document, $collection, $filter = null, int $fetchDepth = -1, bool $forceOverwrite = false): self
    {
        $childrenCollection = new self($dm, $document, $filter, $fetchDepth);
        $childrenCollection->initializeFromCollection($collection, $forceOverwrite);

        return $childrenCollection;
    }

    private function getNode(int $fetchDepth): NodeInterface
    {
        if (!isset($this->node)) {
            $path = $this->dm->getUnitOfWork()->getDocumentId($this->document);
            $this->node = $this->dm->getPhpcrSession()->getNode($path, is_int($fetchDepth) ? $fetchDepth : -1);
        }

        return $this->node;
    }

    /**
     * @return NodeInterface[]
     */
    private function getChildren($childNodes): array
    {
        $uow = $this->dm->getUnitOfWork();
        $locale = $this->locale ?: $uow->getCurrentLocale($this->document);
        $uow->getPrefetchHelper()->prefetch($this->dm, $childNodes, $locale);

        $childDocuments = [];
        foreach ($childNodes as $childNode) {
            $childDocuments[$childNode->getName()] = $uow->getOrCreateProxyFromNode($childNode, $locale);
        }

        return $childDocuments;
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize(): void
    {
        if (!$this->isInitialized()) {
            $this->getOriginalNodeNames();
            $fetchDepth = $this->fetchDepth > 0 ? $this->fetchDepth + 1 : -1;
            $childNodes = $this->getNode($fetchDepth)->getNodes($this->filter);
            $this->collection = new ArrayCollection($this->getChildren($childNodes));
            $this->initialized = self::INITIALIZED_FROM_PHPCR;
        }
    }

    public function contains($element): bool
    {
        if (!$this->isInitialized()) {
            $uow = $this->dm->getUnitOfWork();

            // Shortcut for new documents
            $documentState = $uow->getDocumentState($element);

            if (UnitOfWork::STATE_NEW === $documentState) {
                return false;
            }

            // Document is scheduled for inclusion
            if (UnitOfWork::STATE_MANAGED === $documentState && $uow->isScheduledForInsert($element)) {
                return false;
            }

            $documentId = $uow->getDocumentId($element);
            if (PathHelper::getParentPath($documentId) !== PathHelper::getParentPath($uow->getDocumentId($this->document))) {
                return false;
            }

            $nodeName = PathHelper::getNodeName($documentId);

            return in_array($nodeName, $this->getOriginalNodeNames(), true);
        }

        return parent::contains($element);
    }

    public function containsKey($key): bool
    {
        if (!$this->isInitialized()) {
            return in_array($key, $this->getOriginalNodeNames(), true);
        }

        return parent::containsKey($key);
    }

    public function count(): int
    {
        if (!$this->isInitialized()) {
            return count($this->getOriginalNodeNames());
        }

        return parent::count();
    }

    public function isEmpty(): bool
    {
        if (!$this->isInitialized()) {
            return !$this->count();
        }

        return parent::isEmpty();
    }

    public function slice($offset, $length = null)
    {
        if (!$this->isInitialized()) {
            $nodeNames = $this->getOriginalNodeNames();
            if (!is_numeric($offset)) {
                $offset = array_search($offset, $nodeNames, true);
                if (false === $offset) {
                    return new ArrayCollection();
                }
            }

            $nodeNames = array_slice($nodeNames, $offset, $length);
            $parentPath = $this->getNode($this->fetchDepth)->getPath();
            array_walk($nodeNames, function (&$nodeName) use ($parentPath) {
                $nodeName = "$parentPath/$nodeName";
            });

            $childNodes = $this->dm->getPhpcrSession()->getNodes($nodeNames);

            return $this->getChildren($childNodes);
        }

        if (!is_numeric($offset)) {
            $nodeNames = $this->collection->getKeys();
            $offset = array_search($offset, $nodeNames, true);
            if (false === $offset) {
                return new ArrayCollection();
            }
        }

        return parent::slice($offset, $length);
    }

    /**
     * Return the ordered list of node names of children that existed when the collection was initialized.
     *
     * @return string[]
     */
    public function getOriginalNodeNames(): array
    {
        if (!isset($this->originalNodeNames)) {
            if (self::INITIALIZED_FROM_COLLECTION === $this->initialized) {
                $this->originalNodeNames = $this->collection->getKeys();
            } else {
                $this->originalNodeNames = (array) $this->getNode($this->fetchDepth)->getNodeNames($this->filter);
            }
        }

        return $this->originalNodeNames;
    }

    /**
     * Reset originalNodeNames and mark the collection as non dirty.
     */
    public function takeSnapshot(): void
    {
        if (isset($this->originalNodeNames)) {
            if ($this->isInitialized()) {
                $this->originalNodeNames = $this->collection->getKeys();
            } else {
                unset($this->originalNodeNames);
            }
        }

        parent::takeSnapshot();
    }
}
