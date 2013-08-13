<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;

class SourceDocument extends Source
{
    protected $documentFqn;
    protected $selectorName;

    public function __construct($parent, $documentFqn, $selectorName)
    {
        parent::__construct($parent);
        $this->documentFqn = $documentFqn;
        $this->selectorName = $selectorName;
    }

    public function getCardinalityMap()
    {
        return array();
    }

    public function getDocumentFqn()
    {
        return $this->documentFqn;
    }

    public function getSelectorName()
    {
        return $this->selectorName;
    }
}

