<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\From;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class SelectTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('property', 'Property', array(
                'property_name', 'selector_name',
            )),
        );
    }
}


