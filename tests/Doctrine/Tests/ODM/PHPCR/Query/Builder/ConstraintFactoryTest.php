<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\Where;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class ConstraintFactoryTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('andX', 'ConstraintAndx', array(
            )),
            array('orX', 'ConstraintOrx', array(
            )),
            array('fieldIsset', 'ConstraintFieldIsset', array(
                'alias.propery_name',
            )),
            array('fullTextSearch', 'ConstraintFullTextSearch', array(
                'alias.field', 'full_text_expression',
            )),
            array('same', 'ConstraintSame', array(
                'path', 'alias',
            )),
            array('descendant', 'ConstraintDescendant', array(
                'ancestor_path', 'alias',
            )),
            array('child', 'ConstraintChild', array(
                'parent_path', 'alias',
            )),
            array('not', 'ConstraintNot', array(
            )),
            array('eq', 'ConstraintComparison', array(
                QOMConstants::JCR_OPERATOR_EQUAL_TO
            )),
            array('neq', 'ConstraintComparison', array(
                QOMConstants::JCR_OPERATOR_NOT_EQUAL_TO
            )),
            array('lt', 'ConstraintComparison', array(
                QOMConstants::JCR_OPERATOR_LESS_THAN
            )),
            array('lte', 'ConstraintComparison', array(
                QOMConstants::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO
            )),
            array('gt', 'ConstraintComparison', array(
                QOMConstants::JCR_OPERATOR_GREATER_THAN
            )),
            array('gte', 'ConstraintComparison', array(
                QOMConstants::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO
            )),
            array('like', 'ConstraintComparison', array(
                QOMConstants::JCR_OPERATOR_LIKE
            )),
        );
    }
}


