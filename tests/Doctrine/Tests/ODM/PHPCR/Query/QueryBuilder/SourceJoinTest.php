<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\From;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use Doctrine\ODM\PHPCR\Query\QueryBuilder\SourceJoin;

class SourceJoinTest extends NodeTestCase
{
    public function getNode($args = array())
    {
        $args[] = 'test-join-type';
        return parent::getNode($args);
    }

    public function provideInterface()
    {
        return array(
            array('left', 'SourceJoinLeft', array(
                '/Fqn/To/Class', 'a',
            )),
            array('right', 'SourceJoinRight', array(
            )),
            array('condition', 'SourceJoinCondition', array(
            )),
        );
    }
}



