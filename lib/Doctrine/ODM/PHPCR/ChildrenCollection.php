<?php

namespace Doctrine\ODM\PHPCR;

/**
 * Children collection class
 *
 * This class represents a collection of children of a document which phpcr
 * names match a optional filter
 *
 */
class ChildrenCollection extends PersistentCollection
{
    private $document;
    private $filter;
    private $originalNodenames = array();

    /**
     * Creates a new persistent collection.
     *
     * @param DocumentManager $dm The DocumentManager the collection will be associated with.
     * @param object $document Document instance
     * @param string $filter filter string
     */
    public function __construct(DocumentManager $dm, $document, $filter = null)
    {
        $this->dm = $dm;
        $this->document = $document;
        $this->filter = $filter;
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize()
    {
        if (!$this->initialized) {
            $this->initialized = true;
            $this->collection = $this->dm->getChildren($this->document, $this->filter);
            $this->originalNodenames = $this->collection->getKeys();
        }
    }

    /**
     * Return the ordered list of node names of children that existed when the collection was initialized
     *
     * @return array
     */
    public function getOriginalNodeNames()
    {
        return $this->originalNodenames;
    }
}
