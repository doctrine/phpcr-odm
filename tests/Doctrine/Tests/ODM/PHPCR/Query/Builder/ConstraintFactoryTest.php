<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class ConstraintFactoryTest extends NodeTestCase
{
    public function provideInterface()
    {
        return [
            ['andX', 'ConstraintAndx', [
            ]],
            ['orX', 'ConstraintOrx', [
            ]],
            ['fieldIsset', 'ConstraintFieldIsset', [
                'alias.propery_name',
            ]],
            ['fullTextSearch', 'ConstraintFullTextSearch', [
                'alias.field', 'full_text_expression',
            ]],
            ['same', 'ConstraintSame', [
                'path', 'alias',
            ]],
            ['descendant', 'ConstraintDescendant', [
                'ancestor_path', 'alias',
            ]],
            ['child', 'ConstraintChild', [
                'parent_path', 'alias',
            ]],
            ['not', 'ConstraintNot', [
            ]],
            ['eq', 'ConstraintComparison', [
                QOMConstants::JCR_OPERATOR_EQUAL_TO,
            ]],
            ['neq', 'ConstraintComparison', [
                QOMConstants::JCR_OPERATOR_NOT_EQUAL_TO,
            ]],
            ['lt', 'ConstraintComparison', [
                QOMConstants::JCR_OPERATOR_LESS_THAN,
            ]],
            ['lte', 'ConstraintComparison', [
                QOMConstants::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO,
            ]],
            ['gt', 'ConstraintComparison', [
                QOMConstants::JCR_OPERATOR_GREATER_THAN,
            ]],
            ['gte', 'ConstraintComparison', [
                QOMConstants::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO,
            ]],
            ['like', 'ConstraintComparison', [
                QOMConstants::JCR_OPERATOR_LIKE,
            ]],
        ];
    }
}
