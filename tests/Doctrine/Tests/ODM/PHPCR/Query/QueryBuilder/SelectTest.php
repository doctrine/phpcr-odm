<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\From;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class SelectTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('field', 'Field', array(
                'selector_name.property_name',
            )),
        );
    }
}


