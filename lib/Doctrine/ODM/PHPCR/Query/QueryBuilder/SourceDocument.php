<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Source;

class SourceDocument extends AbstractLeafNode
{
    protected $documentFqn;
    protected $selectorName;

    public function __construct(AbstractNode $parent, $selectorName, $documentFqn)
    {
        $this->documentFqn = $documentFqn;
        $this->selectorName = $selectorName;
        parent::__construct($parent);
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

