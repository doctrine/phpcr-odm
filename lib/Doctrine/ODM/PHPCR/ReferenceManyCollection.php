<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Property collection class
 *
 * This class stores all documents or their proxies referenced by a reference many property 
 */
class ReferenceManyCollection extends MultivaluePropertyCollection
{
    private $referencedNodes;
    private $targetDocument = null;
    
    /**
     * Creates a new persistent collection.
     *
     * @param DocumentManager $dm The DocumentManager the collection will be associated with.
     * @param array $referencedNodes An array of referenced nodes (UUID or path)
     * @param string $targetDocument the objectname of the target documents
     */
    public function __construct(DocumentManager $dm, array $referencedNodes, $targetDocument)
    {
        $this->dm = $dm;
        $this->referencedNodes = $referencedNodes;
        $this->targetDocument = $targetDocument;
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize()
    {
        if (!$this->initialized) {
            $this->initialized = true;

            $referencedDocs = array();
            $referencedNodes = $this->dm->getPhpcrSession()->getNodesByIdentifier($this->referencedNodes);
            $uow = $this->dm->getUnitOfWork();

            $referencedClass = $this->targetDocument
                ? $this->dm->getMetadataFactory()->getMetadataFor(ltrim($this->targetDocument, '\\'))->name
                : null;

            foreach ($referencedNodes as $referencedNode) {
                $proxy = $referencedClass
                    ? $uow->createProxy($referencedNode->getPath(), $referencedClass)
                    : $uow->createProxyFromNode($referencedNode);
                $referencedDocs[] = $proxy;
            }
            
            $this->collection = new ArrayCollection($referencedDocs);
        }
    }

    public function count() 
    {
        return count($this->referencedNodes);
    }
    
    public function isEmpty() 
    {
        return !$this->count();
    }
}
