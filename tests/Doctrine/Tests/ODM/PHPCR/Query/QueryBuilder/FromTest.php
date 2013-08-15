<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\From;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class FromTest extends NodeTestCase
{
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


