<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Source;

class SourceDocument extends AbstractLeafNode
{
    protected $documentFqn;
    protected $alias;

    public function __construct(AbstractNode $parent, $documentFqn, $alias)
    {
        $this->documentFqn = $documentFqn;
        $this->alias = $alias;
        parent::__construct($parent);
    }

    public function getDocumentFqn()
    {
        return $this->documentFqn;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function getNodeType()
    {
        return self::NT_SOURCE;
    }
}

