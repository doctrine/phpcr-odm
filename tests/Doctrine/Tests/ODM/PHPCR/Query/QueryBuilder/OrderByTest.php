<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Where;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class OrderByTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('ascending', 'Ordering', array(
                QOMConstants::JCR_ORDER_ASCENDING,
            )),
            array('descending', 'Ordering', array(
                QOMConstants::JCR_ORDER_DESCENDING,
            )),
        );
    }
}



