<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Builder\TestQueryBuilder;

class TestQueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->node = new TestQueryBuilder;
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
              ->asc()->localName('f')->end()
              ->desc()->field('f.foo')->end()
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
}
