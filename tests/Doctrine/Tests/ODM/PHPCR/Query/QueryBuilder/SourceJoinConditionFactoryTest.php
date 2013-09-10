<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\From;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use Doctrine\ODM\PHPCR\Query\QueryBuilder\SourceJoin;

class SourceJoinConditionFactoryTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('descendant', 'SourceJoinConditionDescendant', array(
                'selector_1', 'selector_2',
            )),
            array('equi', 'SourceJoinConditionEqui', array(
                'selector1.property1', 'selector2.property2',
            )),
            array('childDocument', 'SourceJoinConditionChildDocument', array(
                'child_selector', 'parent_selector',
            )),
            array('sameDocument', 'SourceJoinConditionSameDocument', array(
                'selector_1', 'selector_2', '/path/to/doc',
            )),
        );
    }
}




