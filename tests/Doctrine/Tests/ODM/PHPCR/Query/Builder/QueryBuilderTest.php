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
            array('from', 'From'),
            array('orderBy', 'OrderBy'),
            array('select', 'Select'),
        );
    }

    public function testNonExistantMethod()
    {
        $this->setExpectedException('BadMethodCallException', 
            'Unknown method "foobar" called on class'
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
            // ->from()
            //     ->joinInner()
            //         ->left()->document('foobar', 'a')->end()
            //         ->right()->document('barfoo', 'b')->end()
            //         ->condition()->equi('a.prop_1', 'b.prop_2')->end()
            //     ->end()
            // ->end()
            // ->addJoinInner()
            //     ->right()->document('foobar', 'a')->end()
            //     ->condition()->equi('a.prop_1', 'b.prop_2')->end()
            // ->end()
            // ->addJoinLeftOuter()
            //     ->right()->document('foobar', 'a')->end()
            //     ->condition()->equi('a.prop_1', 'b.prop_2')->end()
            // ->end()
            // ->addJoinRightOuter()
            //     ->right()->document('foobar', 'a')->end()
            //     ->condition()->equi('a.prop_1', 'b.prop_2')->end()
            // ->end()
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
                    ->fieldExists('a.foo') // we can more than two arguments
                    ->fieldExists('a.foo')
                    ->andX()
                      ->eq()->field('f.foo')->literal('bar')->end() // andX works with a single constraint also
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

    public function provideGetNodeByPath()
    {
        return array(
            // valids
            array(
                'where.constraint.operand_static',
                'OperandStaticLiteral',
            ),
            array(
                'order_by.ordering.operand_dynamic[0]',
                'OperandDynamicLocalName',
            ),
            array(
                'order_by.ordering[1].operand_dynamic',
                'OperandDynamicField',
            ),

            // invalids
            array(
                'arf',
                null,
                array('InvalidArgumentException', 'No children'),
            ),
            array(
                'arf.garf',
                null,
                array('InvalidArgumentException', 'No children'),
            ),
            array(
                'arf[123].garf',
                null,
                array('InvalidArgumentException', 'No child of node'),
            ),
        );
    }

    /**
     * @dataProvider provideGetNodeByPath
     */
    public function testGetNodeByPath($path, $expectedClassName, $expectedException = null)
    {
        $this->node
            ->where()->eq()->field('f.foo')->literal('foo.bar')->end()->end()
            ->orderBy()
              ->ascending()->localName('f')->end()
              ->descending()->field('f.foo')->end()
              ->end();

        if ($expectedException) {
            list($exceptionType, $exceptionMessage) = $expectedException;
            $this->setExpectedException($exceptionType, $exceptionMessage);
        }

        $node = $this->node->getNodeByPath($path);

        if (!$expectedException) {
            $this->assertInstanceOf('Doctrine\\ODM\\PHPCR\\Query\\Builder\\'.$expectedClassName, $node);
        }
    }

    public function testFirstMaxResult()
    {
        $this->node->setMaxResults(123);
        $this->node->setFirstResult(4);

        $this->assertEquals(123, $this->node->getMaxResults());
        $this->assertEquals(4, $this->node->getFirstResult());
    }
}
