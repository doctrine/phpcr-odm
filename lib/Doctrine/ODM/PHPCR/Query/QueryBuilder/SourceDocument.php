<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;

class SourceDocument extends AbstractLeafNode
{
    protected $documentFqn;
    protected $selectorName;

    public function __construct($parent, $documentFqn, $selectorName)
    {
        parent::__construct($parent);
        $this->documentFqn = $documentFqn;
        $this->selectorName = $selectorName;
    }

    public function getDocumentFqn()
    {
        return $this->documentFqn;
    }

    public function getSelectorName()
    {
        return $this->selectorName;
    }

    public function getNodeType()
    {
        return self::NT_SOURCE;
    }
}

