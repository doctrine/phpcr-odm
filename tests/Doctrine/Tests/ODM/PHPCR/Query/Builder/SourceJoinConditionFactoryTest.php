<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\From;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use Doctrine\ODM\PHPCR\Query\Builder\SourceJoin;

class SourceJoinConditionFactoryTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('descendant', 'SourceJoinConditionDescendant', array(
                'alias_1', 'alias_2',
            )),
            array('equi', 'SourceJoinConditionEqui', array(
                'alias1.property1', 'alias2.property2',
            )),
            array('child', 'SourceJoinConditionChildDocument', array(
                'child_alias', 'parent_alias',
            )),
            array('same', 'SourceJoinConditionSameDocument', array(
                'alias_1', 'alias_2', '/path/to/doc',
            )),
        );
    }
}
