<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class OrderByTest extends NodeTestCase
{
    public function provideInterface()
    {
        return [
            ['asc', 'Ordering', [
                QOMConstants::JCR_ORDER_ASCENDING,
            ]],
            ['desc', 'Ordering', [
                QOMConstants::JCR_ORDER_DESCENDING,
            ]],
        ];
    }
}
