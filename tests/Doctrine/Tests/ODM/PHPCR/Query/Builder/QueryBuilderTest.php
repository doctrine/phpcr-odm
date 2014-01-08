<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;

class QueryBuilderTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('where', 'Where'),
            array('andWhere', 'WhereAnd'), // andWhere adds Where if no existing Where
            array('orWhere', 'WhereOr'), // andWhere adds Where if no existing Where
            array('from', 'From', array('a')),
            array('orderBy', 'OrderBy'),
            array('select', 'Select'),
        );
    }

    public function testNonExistantMethod()
    {
        $this->setExpectedException('BadMethodCallException',
            'Unknown method "foobar" called on node'
        );
        $this->node->foobar();
    }

    // this test serves no other purpose than to demonstrate
    // the API
    public function testApi1()
    {
        $this->node
            ->select()
                ->field('a.prop_1')
                ->field('a.prop_2')
            ->end()
            ->addSelect()
                ->field('a.prop_3')
                ->field('a.prop_4')
            ->end()
            ->fromDocument('foobar', 'a')
            ->from('a')
                ->joinInner()
                    ->left()->document('foobar', 'a')->end()
                    ->right()->document('barfoo', 'b')->end()
                    ->condition()->equi('a.prop_1', 'b.prop_2')->end()
                ->end()
            ->end()
            ->addJoinInner()
                ->right()->document('foobar', 'a')->end()
                ->condition()->equi('a.prop_1', 'b.prop_2')->end()
            ->end()
            ->addJoinLeftOuter()
                ->right()->document('foobar', 'a')->end()
                ->condition()->equi('a.prop_1', 'b.prop_2')->end()
            ->end()
            ->addJoinRightOuter()
                ->right()->document('foobar', 'a')->end()
                ->condition()->equi('a.prop_1', 'b.prop_2')->end()
            ->end()
            ->where()
                ->andX()
                    ->eq()
                        ->field('a.foobar')
                        ->literal('foo_value')
                    ->end()
                    ->like()
                        ->name('my_doc')
                        ->parameter('my_var')
                    ->end()
                    ->fieldIsset('a.foo') // we can more than two arguments
                    ->fieldIsset('a.foo')
                    ->andX()
                      ->eq()->field('f.foo')->literal('bar')->end() // andX works with a single constraint also
                    ->end()
                ->end()
            ->end()
            ->andWhere()
                ->fieldIsset('alias_1.foobar')
            ->end()
            ->orWhere()
                ->fieldIsset('alias_1.foobar')
            ->end()
            ->orderBy()
                ->asc()->name('a')->end()
                ->desc()->name('b')->end()
            ->end()
            ->addOrderBy()
                ->asc()->name('c')->end()
            ->end()
        ;
    }

    public function testPrimaryAlias()
    {
        $this->node->from('f');
        $this->assertEquals('f', $this->node->getPrimaryAlias());

        $this->node->fromDocument('Foobar', 'f');
        $this->assertEquals('f', $this->node->getPrimaryAlias());
    }
}
