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
                'selector_name',
            )),
            array('length', 'OperandDynamicFullTextSearchScore', array(
                'selector_name.property_name',
            )),
            array('lowerCase', 'OperandDynamicLowerCase', array(
            )),
            array('upperCase', 'OperandDynamicUpperCase', array(
            )),
            array('name', 'OperandDynamicName', array(
                'selector_name',
            )),
            array('localName', 'OperandDynamicLocalName', array(
                'selector_name',
            )),
            array('field', 'OperandDynamicField', array(
                'selector_name.property_name',
            )),
        );
    }
}


