<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

class From extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            'Source' => array(1, 1)
        );
    }

    public function document($documentFqn, $selectorName)
    {
        return $this->addChild(new SourceDocument($this, $documentFqn, $selectorName));
    }

    public function join()
    {
        return $this->addChild(new SourceJoin($this));
    }
}
