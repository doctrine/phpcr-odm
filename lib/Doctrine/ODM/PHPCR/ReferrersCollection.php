<?php

namespace Doctrine\ODM\PHPCR;

/**
 * Referrer collection class
 *
 * This class represents a collection of referrers of a document which phpcr
 * names match a optional name
 *
 */
class ReferrersCollection extends PersistentCollection
{
    private $document;
    private $type;
    private $name;

    public function __construct(DocumentManager $dm, $document, $type = null, $name = null)
    {
        $this->dm = $dm;
        $this->document = $document;
        $this->type = $type;
        $this->name = $name;
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize()
    {
        if (!$this->initialized) {
            $this->initialized = true;

            $this->collection = $this->dm->getReferrers($this->document, $this->type, $this->name);
        }
    }
}
