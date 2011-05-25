<?php

namespace Doctrine\ODM\PHPCR;

/**
 * Children collection class
 *
 * This class represents a collection of children of a document which phpcr
 * names match a optional filter
 *
 */
class ChildrenCollection extend PersistentCollection
{
    private $document;
    private $dm;
    private $filter;

    public function __construct($document, DocumentManager $dm, $filter = '')
    {
        $this->document = $document;
        $this->dm = $dm;
        $this->filter = $filter;
    }

    protected function load()
    {
        $this->col = $this->dm->getChildren($this->document, $this->filter);
    }
}
