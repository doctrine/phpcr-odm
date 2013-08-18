<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Where;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class WhereTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('andX', 'ConstraintAndx', array(
            )),
            array('orX', 'ConstraintOrx', array(
            )),
            array('propertyExists', 'ConstraintPropertyExists', array(
                'property_name', 'selector_name',
            )),
            array('fullTextSearch', 'ConstraintFullTextSearch', array(
                'property_name', 'full_text_expression', 'selector_name',
            )),
            array('sameDocument', 'ConstraintSameDocument', array(
                'path', 'selector_name',
            )),
            array('descendantDocument', 'ConstraintDescendantDocument', array(
                'ancestor_path', 'selector_name',
            )),
            array('childDocument', 'ConstraintChildDocument', array(
                'parent_path', 'selector_name',
            )),
            array('not', 'ConstraintNot', array(
            )),
            array('eq', 'ConstraintComparisson', array(
                QOMConstants::JCR_OPERATOR_EQUAL_TO
            )),
            array('neq', 'ConstraintComparisson', array(
                QOMConstants::JCR_OPERATOR_NOT_EQUAL_TO
            )),
            array('lt', 'ConstraintComparisson', array(
                QOMConstants::JCR_OPERATOR_LESS_THAN
            )),
            array('lte', 'ConstraintComparisson', array(
                QOMConstants::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO
            )),
            array('gt', 'ConstraintComparisson', array(
                QOMConstants::JCR_OPERATOR_GREATER_THAN
            )),
            array('gte', 'ConstraintComparisson', array(
                QOMConstants::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO
            )),
            array('like', 'ConstraintComparisson', array(
                QOMConstants::JCR_OPERATOR_LIKE
            )),
        );
    }
}


