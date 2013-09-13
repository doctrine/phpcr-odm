<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Where;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class OperandStaticFactoryTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('literal', 'OperandStaticLiteral', array(
                'value',
            )),
            array('parameter', 'OperandStaticParameter', array(
                'variable_name',
            )),
        );
    }
}


