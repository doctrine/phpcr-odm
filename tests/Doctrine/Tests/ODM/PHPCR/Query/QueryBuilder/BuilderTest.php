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

    public function testApi1()
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
            ->end()
            ->orderBy()
                ->ascending()->documentName('a')->end()
                ->descending()->documentName('b')->end()
            ->end();
    }

    public function testApi2()
    {
        $this->node
            ->from()
                ->joinInner()
                    ->left()->document('foobar', 'a')->end()
                    ->right()->document('barfoo', 'b')->end()
                    ->condition()->equi('prop_1', 'a', 'prop_2', 'b')->end()
                ->end()
            ->end();
    }
}
