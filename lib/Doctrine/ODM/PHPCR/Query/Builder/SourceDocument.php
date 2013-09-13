<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;

class SourceDocument extends AbstractLeafNode
{
    protected $documentFqn;
    protected $selectorName;

    public function __construct(AbstractNode $parent, $documentFqn, $selectorName)
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

