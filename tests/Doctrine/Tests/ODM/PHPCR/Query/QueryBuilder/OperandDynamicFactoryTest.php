<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Where;
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
            array('documentName', 'OperandDynamicDocumentName', array(
                'selector_name',
            )),
            array('documentLocalName', 'OperandDynamicDocumentLocalName', array(
                'selector_name',
            )),
            array('propertyValue', 'OperandDynamicPropertyValue', array(
                'selector_name.property_name',
            )),
        );
    }
}


