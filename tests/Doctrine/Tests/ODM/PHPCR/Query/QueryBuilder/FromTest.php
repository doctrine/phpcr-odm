<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\From;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class FromTest extends NodeTestCase
{
    public function getNode()
    {
        return new From;
    }

    public function provideInterface()
    {
        return array(
            array('document', 'Doctrine\ODM\PHPCR\Query\QueryBuilder\SourceDocument', array(
                '/Fqn/To/Class', 'a',
            )),
            array('joinInner', 'Doctrine\ODM\PHPCR\Query\QueryBuilder\SourceJoin', array(
                QOMConstants::JCR_JOIN_TYPE_INNER,
            )),
            array('joinLeftOuter', 'Doctrine\ODM\PHPCR\Query\QueryBuilder\SourceJoin', array(
                QOMConstants::JCR_JOIN_TYPE_LEFT_OUTER,
            )),
            array('joinRightOuter', 'Doctrine\ODM\PHPCR\Query\QueryBuilder\SourceJoin', array(
                QOMConstants::JCR_JOIN_TYPE_RIGHT_OUTER,
            )),
        );
    }
}


