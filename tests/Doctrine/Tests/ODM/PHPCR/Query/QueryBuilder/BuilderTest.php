<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Builder;

class BuilderTest extends NodeTestCase
{
    public function getNode()
    {
        return new Builder();
    }

    public function provideInterface()
    {
        return array(
            array('where', 'Doctrine\ODM\PHPCR\Query\QueryBuilder\Where'),
            array('from', 'Doctrine\ODM\PHPCR\Query\QueryBuilder\From'),
            array('orderBy', 'Doctrine\ODM\PHPCR\Query\QueryBuilder\OrderBy'),
            array('select', 'Doctrine\ODM\PHPCR\Query\QueryBuilder\Select'),
        );
    }
}

