<?php

namespace Doctrine\ODM\PHPCR\Event;

use Doctrine\Common\Persistence\Event\PreUpdateEventArgs as BasePreUpdateEventArgs;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;

class PreUpdateEventArgs extends BasePreUpdateEventArgs
{
    /**
     * @var array
     */
    private $documentChangeSet;

    /**
     * Constructor.
     *
     * @param object                   $document
     * @param DocumentManagerInterface $objectManager
     * @param array                    $changeSet
     */
    public function __construct($document, DocumentManagerInterface $documentManager, array &$changeSet)
    {
        $fieldChangeSet = [];
        if (isset($changeSet['fields'])) {
            $fieldChangeSet = &$changeSet['fields'];
        }

        parent::__construct($document, $documentManager, $fieldChangeSet);

        $this->documentChangeSet = &$changeSet;
    }

    /**
     * Retrieves the document changeset.
     *
     * Currently this structure contains 2 keys:
     *  'fields' - changes to field values
     *  'reorderings' - changes in the order of collections
     *
     * @return array
     */
    public function getDocumentChangeSet()
    {
        return $this->documentChangeSet;
    }
}
