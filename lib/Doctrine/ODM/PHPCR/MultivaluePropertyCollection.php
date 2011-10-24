<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\Collection;

/**
 * Property collection class
 *
 * This class stores 
 */
class MultivaluePropertyCollection extends PersistentCollection
{
    public function __construct(Collection $coll, $isDirty = false)
    {
        $this->coll = $coll;
        $this->isDirty = $isDirty;
        $this->initialized = true;
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize()
    {
        $this->initialized = true;
    }
}

