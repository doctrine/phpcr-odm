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
    private $dm;
    private $name;

    public function __construct($document, DocumentManager $dm, $name= null)
    {
        $this->document = $document;
        $this->dm = $dm;
        $this->name = $name;
    }

    protected function load()
    {
        if (null === $this->col) {
            $this->col = $this->dm->getReferrers($this->document, $this->name);
        }
    }
}
