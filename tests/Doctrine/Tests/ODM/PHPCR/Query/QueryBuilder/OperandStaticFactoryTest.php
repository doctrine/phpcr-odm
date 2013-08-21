<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Where;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class OperandStaticFactoryTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('literal', 'OperandStaticLiteral', array(
                'value',
            )),
            array('bindVariable', 'OperandStaticBindVariable', array(
                'variable_name',
            )),
        );
    }
}


