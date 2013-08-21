<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class OrderBy extends AbstractNode
{
    public function getCardinalityMap()
    {
        return array(
            'Ordering' => array(0, null)
        );
    }

    public function ascending()
    {
        return new Ordering($this, QOMConstants::JCR_ORDER_ASCENDING);
    }

    public function descending()
    {
        return new Ordering($this, QOMConstants::JCR_ORDER_DESCENDING);
    }
}


