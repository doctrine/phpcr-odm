<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\Common\Collections\Collection;

/**
 * Property collection class
 *
 * This class stores all values of a multivalue property
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
     * Just there to fulfill the interface requirements of PersistentCollection
     */
    public function initialize()
    {
        $this->initialized = true;
    }
}

