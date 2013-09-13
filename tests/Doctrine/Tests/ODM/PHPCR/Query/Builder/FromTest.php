<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\From;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class FromTest extends NodeTestCase
{
    /**
     * @dataProvider provideInterface
     */
    public function testInterface($method, $type, $args = array())
    {
        $this->markTestSkipped('Joins temporarily disabled');
    }

    public function provideInterface()
    {
        return array(
            array('document', 'SourceDocument', array(
                '/Fqn/To/Class', 'a',
            )),
            array('joinInner', 'SourceJoin', array(
                QOMConstants::JCR_JOIN_TYPE_INNER,
            )),
            array('joinLeftOuter', 'SourceJoin', array(
                QOMConstants::JCR_JOIN_TYPE_LEFT_OUTER,
            )),
            array('joinRightOuter', 'SourceJoin', array(
                QOMConstants::JCR_JOIN_TYPE_RIGHT_OUTER,
            )),
        );
    }
}


