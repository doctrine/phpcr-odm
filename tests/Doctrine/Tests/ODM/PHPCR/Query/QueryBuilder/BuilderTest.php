<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Builder;

class BuilderTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('where', 'Where'),
            array('from', 'From'),
            array('orderBy', 'OrderBy'),
            array('select', 'Select'),
        );
    }
}

