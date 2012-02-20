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

    private $session;
    private $referencedDocs;

    /**
     * Creates a new persistent collection.
     *
     * @param DocumentManager $dm The DocumentManager the collection will be associated with.
     * @param object $document Document instance
     * @param string $filter filter string
     */
    public function __construct(DocumentManager $dm, \Jackalope\Session $session, UnitOfWork $uow, array $referencedDocUUIDs, $targetDocument)
    {
        $this->dm = $dm;
        $this->session = $session;
        $this->uow = $uow;
        $this->referencedDocUUIDs = $referencedDocUUIDs;
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
            $referencedNodes = $this->session->getNodesByIdentifier($this->referencedDocUUIDs);
            foreach ($referencedNodes as $referencedNode) {
                $referencedClass = isset($targetDocument) ? $this->dm->getMetadataFactory()->getMetadataFor(ltrim($targetDocument, '\\'))->name : null;
                $proxy = $referencedClass ? $this->uow->createProxy($referencedNode->getPath(), $referencedClass) : $this->uow->createProxyFromNode($referencedNode);
                $referencedDocs[] = $proxy;
            }

            $this->collection = new ArrayCollection($referencedDocs);
        }
    }
    
}