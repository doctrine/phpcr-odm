<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Where;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class OrderByTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('asc', 'Ordering', array(
                QOMConstants::JCR_ORDER_ASCENDING,
            )),
            array('desc', 'Ordering', array(
                QOMConstants::JCR_ORDER_DESCENDING,
            )),
        );
    }
}



