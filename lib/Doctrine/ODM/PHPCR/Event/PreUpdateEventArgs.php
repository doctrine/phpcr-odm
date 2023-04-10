<?php

namespace Doctrine\ODM\PHPCR\Event;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\Persistence\Event\PreUpdateEventArgs as BasePreUpdateEventArgs;

class PreUpdateEventArgs extends BasePreUpdateEventArgs
{
    private array $documentChangeSet;

    public function __construct(object $document, DocumentManagerInterface $documentManager, array &$changeSet)
    {
        $fieldChangeSet = [];
        if (array_key_exists('fields', $changeSet)) {
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
     */
    public function getDocumentChangeSet(): array
    {
        return $this->documentChangeSet;
    }
}
