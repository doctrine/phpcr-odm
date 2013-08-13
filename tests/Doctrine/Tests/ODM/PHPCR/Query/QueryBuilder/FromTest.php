<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\From;

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
            array('join', 'Doctrine\ODM\PHPCR\Query\QueryBuilder\SourceJoin', array(
            )),
        );
    }
}


