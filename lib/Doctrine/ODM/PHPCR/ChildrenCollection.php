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
    private $document;
    private $filter;
    private $fetchDepth;
    private $originalNodeNames;
    private $node;

    /**
     * Creates a new persistent collection.
     *
     * @param DocumentManagerInterface $dm         The DocumentManager the collection will be associated with.
     * @param object                   $document   The parent document instance
     * @param string|array             $filter     Filter string or array of filter string
     * @param int                      $fetchDepth Optional fetch depth, -1 to not override.
     * @param string                   $locale     The locale to use during the loading of this collection
     */
    public function __construct(DocumentManagerInterface $dm, $document, $filter = null, $fetchDepth = -1, $locale = null)
    {
        $this->dm = $dm;
        $this->document = $document;
        $this->filter = $filter;
        $this->fetchDepth = $fetchDepth;
        $this->locale = $locale;
    }

    /**
     * @param DocumentManagerInterface $dm             The DocumentManager the collection will be associated with.
     * @param object                   $document       The parent document instance
     * @param array|Collection         $collection     The collection to initialize with
     * @param string|array             $filter         Filter string or array of filter string
     * @param int                      $fetchDepth     Optional fetch depth, -1 to not override.
     * @param bool                     $forceOverwrite If to force overwrite the state in the database to the state of the collection
     *
     * @return ChildrenCollection
     */
    public static function createFromCollection(DocumentManagerInterface $dm, $document, $collection, $filter = null, $fetchDepth = -1, $forceOverwrite = false)
    {
        $childrenCollection = new self($dm, $document, $filter, $fetchDepth);
        $childrenCollection->initializeFromCollection($collection, $forceOverwrite);

        return $childrenCollection;
    }

    /**
     * @param $fetchDepth
     *
     * @return NodeInterface
     */
    private function getNode($fetchDepth)
    {
        if (null === $this->node) {
            $path = $this->dm->getUnitOfWork()->getDocumentId($this->document);
            $this->node = $this->dm->getPhpcrSession()->getNode($path, $fetchDepth);
        }

        return $this->node;
    }

    /**
     * @param $childNodes
     *
     * @return array
     */
    private function getChildren($childNodes)
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
    public function initialize()
    {
        if (!$this->isInitialized()) {
            $this->getOriginalNodeNames();
            $fetchDepth = $this->fetchDepth > 0 ? $this->fetchDepth + 1 : -1;
            $childNodes = $this->getNode($fetchDepth)->getNodes($this->filter);
            $this->collection = new ArrayCollection($this->getChildren($childNodes));
            $this->initialized = self::INITIALIZED_FROM_PHPCR;
        }
    }

    /** {@inheritdoc} */
    public function contains($element)
    {
        if (!$this->isInitialized()) {
            $uow = $this->dm->getUnitOfWork();

            // Shortcut for new documents
            $documentState = $uow->getDocumentState($element);

            if ($documentState === UnitOfWork::STATE_NEW) {
                return false;
            }

            // Document is scheduled for inclusion
            if ($documentState === UnitOfWork::STATE_MANAGED && $uow->isScheduledForInsert($element)) {
                return false;
            }

            $documentId = $uow->getDocumentId($element);
            if (PathHelper::getParentPath($documentId) !== PathHelper::getParentPath($uow->getDocumentId($this->document))) {
                return false;
            }

            $nodeName = PathHelper::getNodeName($documentId);

            return in_array($nodeName, $this->getOriginalNodeNames());
        }

        return parent::contains($element);
    }

    /** {@inheritdoc} */
    public function containsKey($key)
    {
        if (!$this->isInitialized()) {
            return in_array($key, $this->getOriginalNodeNames());
        }

        return parent::containsKey($key);
    }

    /** {@inheritdoc} */
    public function count()
    {
        if (!$this->isInitialized()) {
            return count($this->getOriginalNodeNames());
        }

        return parent::count();
    }

    /** {@inheritdoc} */
    public function isEmpty()
    {
        if (!$this->isInitialized()) {
            return !$this->count();
        }

        return parent::isEmpty();
    }

    /** {@inheritdoc} */
    public function slice($offset, $length = null)
    {
        if (!$this->isInitialized()) {
            $nodeNames = $this->getOriginalNodeNames();
            if (!is_numeric($offset)) {
                $offset = array_search($offset, $nodeNames);
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
            $offset = array_search($offset, $nodeNames);
            if (false === $offset) {
                return new ArrayCollection();
            }
        }

        return parent::slice($offset, $length);
    }

    /**
     * Return the ordered list of node names of children that existed when the collection was initialized.
     *
     * @return array
     */
    public function getOriginalNodeNames()
    {
        if (null === $this->originalNodeNames) {
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
    public function takeSnapshot()
    {
        if (is_array($this->originalNodeNames)) {
            if ($this->isInitialized()) {
                $this->originalNodeNames = $this->collection->getKeys();
            } else {
                $this->originalNodeNames = null;
            }
        }

        parent::takeSnapshot();
    }
}
