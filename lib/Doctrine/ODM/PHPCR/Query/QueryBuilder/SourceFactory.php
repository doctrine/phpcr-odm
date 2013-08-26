<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

abstract class SourceFactory extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            self::NT_SOURCE => array(1, 1)
        );
    }

    public function getNodeType()
    {
        return self::NT_SOURCE_FACTORY;
    }

    public function document($documentFqn, $selectorName)
    {
        return $this->addChild(new SourceDocument($this, $documentFqn, $selectorName));
    }

    public function joinInner()
    {
        return $this->addChild(new SourceJoin($this,
            QOMConstants::JCR_JOIN_TYPE_INNER
        ));
    }

    public function joinLeftOuter()
    {
        return $this->addChild(new SourceJoin($this, 
            QOMConstants::JCR_JOIN_TYPE_LEFT_OUTER
        ));
    }

    public function joinRightOuter()
    {
        return $this->addChild(new SourceJoin($this, 
            QOMConstants::JCR_JOIN_TYPE_RIGHT_OUTER
        ));
    }
}
