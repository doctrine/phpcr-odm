<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\Collection;

/**
 * Property collection class
 *
 * This class stores all documents or their proxies referenced by a reference many property 
 */
class ReferenceManyCollection extends PersistentCollection
{
    public function __construct(Collection $coll, $isDirty = false)
    {
        $this->coll = $coll;
        $this->isDirty = $isDirty;
        $this->initialized = true;
    }

    /**
     * Just there to fulfill the interface requirements of PersistentCollection
     */
    public function initialize()
    {
        $this->initialized = true;
    }
}

