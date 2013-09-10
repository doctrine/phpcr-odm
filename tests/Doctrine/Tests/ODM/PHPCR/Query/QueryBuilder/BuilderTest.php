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
                ->property('a.prop_1')
                ->property('a.prop_2')
            ->end()
            ->addSelect()
                ->property('a.prop_3')
                ->property('a.prop_4')
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
                        ->propertyValue('a.foobar')
                        ->literal('foo_value')
                    ->end()
                    ->like()
                        ->documentName('my_doc')
                        ->bindVariable('my_var')
                    ->end()
                ->end()
            ->end()
            ->andWhere()
                ->propertyExists('sel_1.foobar')
            ->end()
            ->orWhere()
                ->propertyExists('sel_1.foobar')
            ->end()
            ->orderBy()
                ->ascending()->documentName('a')->end()
                ->descending()->documentName('b')->end()
            ->end()
            ->addOrderBy()
                ->ascending()->documentName('c')->end()
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
