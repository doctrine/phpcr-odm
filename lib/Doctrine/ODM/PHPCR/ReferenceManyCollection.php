<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;

/**
 * Property collection class.
 *
 * This class stores all documents or their proxies referenced by a reference many property
 */
class ReferenceManyCollection extends PersistentCollection
{
    const REFERENCE_TYPE_PATH = 'path';
    const REFERENCE_TYPE_UUID = 'uuid';

    private $document;
    private $property;
    private $referencedNodes;
    private $targetDocument;
    private $originalReferencePaths;
    private $referenceType;

    /**
     * Creates a new persistent collection.
     *
     * @param DocumentManagerInterface $dm              The DocumentManager the collection will be associated with.
     * @param object                   $document        The document with the references property
     * @param string                   $property        The node property name with the multivalued references
     * @param array                    $referencedNodes An array of referenced nodes (UUID or path)
     * @param string                   $targetDocument  The class name of the target documents
     * @param string                   $locale          The locale to use during the loading of this collection
     * @param string                   $referenceType   Identifiers used for reference nodes in this collection, either path or default uuid
     */
    public function __construct(DocumentManagerInterface $dm, $document, $property, array $referencedNodes, $targetDocument, $locale = null, $referenceType = self::REFERENCE_TYPE_UUID)
    {
        $this->dm = $dm;
        $this->document = $document;
        $this->property = $property;
        $this->referencedNodes = $referencedNodes;
        $this->targetDocument = $targetDocument;
        $this->locale = $locale;
        $this->referenceType = $referenceType;
    }

    /**
     * @param DocumentManagerInterface $dm             The DocumentManager the collection will be associated with.
     * @param object                   $document       The document with the references property
     * @param string                   $property       The node property name with the multivalued references
     * @param array|Collection         $collection     The collection to initialize with
     * @param string                   $targetDocument The class name of the target documents
     * @param bool                     $forceOverwrite If to force the database to be forced to the state of the collection
     *
     * @return ReferenceManyCollection
     */
    public static function createFromCollection(DocumentManagerInterface $dm, $document, $property, $collection, $targetDocument, $forceOverwrite = false)
    {
        $referenceCollection = new self($dm, $document, $property, [], $targetDocument);
        $referenceCollection->initializeFromCollection($collection, $forceOverwrite);

        return $referenceCollection;
    }

    /** {@inheritdoc} */
    public function refresh()
    {
        try {
            $property = $this->dm->getNodeForDocument($this->document)->getProperty($this->property);
            $this->referencedNodes = $property->getString();
        } catch (InvalidArgumentException $e) {
            $this->referencedNodes = [];
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
            $referencedDocs = [];
            if (self::REFERENCE_TYPE_UUID === $this->referenceType) {
                $referencedNodes = $this->dm->getPhpcrSession()->getNodesByIdentifier($this->referencedNodes);
            } else {
                $referencedNodes = $this->dm->getPhpcrSession()->getNodes($this->referencedNodes);
            }

            $uow = $this->dm->getUnitOfWork();
            $uow->getPrefetchHelper()->prefetch($this->dm, $referencedNodes, $this->locale);

            $this->originalReferencePaths = [];
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

    /** {@inheritdoc} */
    public function count()
    {
        if (!$this->isInitialized()) {
            return count($this->referencedNodes);
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

    /**
     * Return the ordered list of references that existed when the collection was initialized.
     *
     * @return array
     */
    public function getOriginalPaths()
    {
        if (null === $this->originalReferencePaths) {
            $this->originalReferencePaths = [];
            if (self::INITIALIZED_FROM_COLLECTION === $this->initialized) {
                $uow = $this->dm->getUnitOfWork();
                foreach ($this->collection as $reference) {
                    $this->originalReferencePaths[] = $uow->getDocumentId($reference);
                }
            } else {
                if (self::REFERENCE_TYPE_UUID === $this->referenceType) {
                    $nodes = $this->dm->getPhpcrSession()->getNodesByIdentifier($this->referencedNodes);
                    foreach ($nodes as $node) {
                        $this->originalReferencePaths[] = $node->getPath();
                    }
                } else {
                    $this->originalReferencePaths = $this->referencedNodes;
                }
            }
        }

        return $this->originalReferencePaths;
    }

    /**
     * Reset original reference paths and mark the collection as non dirty.
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
