<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Builder;

class BuilderTest extends NodeTestCase
{
    public function provideInterface()
    {
        return array(
            array('where', 'Where'),
            array('andWhere', 'WhereAnd'), // andWhere adds Where if no existing Where
            array('orWhere', 'WhereOr'), // andWhere adds Where if no existing Where
            array('from', 'From'),
            array('orderBy', 'OrderBy'),
            array('select', 'Select'),
        );
    }

    public function testNonExistantMethod()
    {
        $this->setExpectedException('BadMethodCallException', 
            'Unknown method "foobar" called on class "Doctrine\ODM\PHPCR\Query\QueryBuilder\Builder", did you mean one of: "where, andWhere, orWhere'
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
            ->from()
                ->joinInner()
                    ->left()->document('foobar', 'a')->end()
                    ->right()->document('barfoo', 'b')->end()
                    ->condition()->equi('a.prop_1', 'b.prop_2')->end()
                ->end()
            ->end()
            ->fromDocument('foobar', 'a')
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
                ->end()
            ->end()
            ->andWhere()
                ->fieldExists('sel_1.foobar')
            ->end()
            ->orWhere()
                ->fieldExists('sel_1.foobar')
            ->end()
            ->orderBy()
                ->ascending()->name('a')->end()
                ->descending()->name('b')->end()
            ->end()
            ->addOrderBy()
                ->ascending()->name('c')->end()
            ->end();
    }

    public function testFirstMaxResult()
    {
        $this->node->setMaxResults(123);
        $this->node->setFirstResult(4);

        $this->assertEquals(123, $this->node->getMaxResults());
        $this->assertEquals(4, $this->node->getFirstResult());
    }
}
