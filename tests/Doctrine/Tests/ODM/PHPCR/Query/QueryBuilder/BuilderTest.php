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

    public function testApi()
    {
        $this->node
            ->select()->property('foobar', 'a')->property('barfoo', 'a')->end()
            ->from()->document('Foobar', 'a')->end()
            ->where()
                ->andX()
                    ->eq()
                        ->left()->propertyValue('foobar', 'a')->end()
                        ->right()->literal('foo_value')->end()
                    ->end()
                    ->like()
                        ->left()->documentName('my_doc')->end()
                        ->right()->bindVariable('my_var')->end()
                    ->end()
                ->end()
            ->end();
    }
}
