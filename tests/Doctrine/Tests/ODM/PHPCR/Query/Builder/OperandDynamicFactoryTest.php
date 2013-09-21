<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Where;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class OperandDynamicFactoryTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('fullTextSearchScore', 'OperandDynamicFullTextSearchScore', array(
                'alias',
            )),
            array('length', 'OperandDynamicFullTextSearchScore', array(
                'alias.field',
            )),
            array('lowerCase', 'OperandDynamicLowerCase', array(
            )),
            array('upperCase', 'OperandDynamicUpperCase', array(
            )),
            array('name', 'OperandDynamicName', array(
                'alias',
            )),
            array('localName', 'OperandDynamicLocalName', array(
                'alias',
            )),
            array('field', 'OperandDynamicField', array(
                'alias.field',
            )),
        );
    }
}


