<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\From;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use Doctrine\ODM\PHPCR\Query\Builder\SourceJoin;

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
            array('condition', 'SourceJoinConditionFactory', array(
            )),
        );
    }
}
