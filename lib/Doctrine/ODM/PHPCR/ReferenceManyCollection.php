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
    private $uow;
    private $referencedDocUUIDs;
    private $targetDocument = null;
    
    /**
     * Creates a new persistent collection.
     *
     * @param DocumentManager $dm The DocumentManager the collection will be associated with.
     * @param \PHPCR\SessionInterface $session The current session
     * @param UnitOfWork $uow The Doctrine Unit Of Work instance
     * @param array $referencedDocUUIDs An array of referenced UUIDs
     * @param string $targetDocument the objectname of the target documents
     */
    public function __construct(DocumentManager $dm, \PHPCR\SessionInterface $session, UnitOfWork $uow, array $referencedDocUUIDs, $targetDocument)
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
                $referencedClass = $this->targetDocument ? $this->dm->getMetadataFactory()->getMetadataFor(ltrim($this->targetDocument, '\\'))->name : null;
                $proxy = $referencedClass ? $this->uow->createProxy($referencedNode->getPath(), $referencedClass) : $this->uow->createProxyFromNode($referencedNode);
                $referencedDocs[] = $proxy;
            }
            
            $this->collection = new ArrayCollection($referencedDocs);
        }
    }
}