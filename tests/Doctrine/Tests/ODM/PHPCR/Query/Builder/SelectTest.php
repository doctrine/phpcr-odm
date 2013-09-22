<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\From;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class SelectTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('field', 'Field', array(
                'alias.field',
            )),
        );
    }
}


