<?php

namespace Doctrine\Tests\ODM\PHPCR\Tools\Test;

use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Tools\Test\QueryBuilderTester;

class QueryBuilderTesterTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->qb = new QueryBuilder;
        $this->qb->where()->andX()
            ->eq()->field('a.foo')->literal('Foo')->end()
            ->eq()->field('a.foo')->literal('Bar');

        $this->qbTester = new QueryBuilderTester($this->qb);
    }

    public function testDumpPaths()
    {
        $res = $this->qbTester->dumpPaths();
        $this->assertEquals(<<<HERE
where (Where)
where.constraint (ConstraintAndx)
where.constraint.constraint (ConstraintComparison)
where.constraint.constraint.operand_dynamic (OperandDynamicField)
where.constraint.constraint.operand_static (OperandStaticLiteral)
where.constraint.constraint (ConstraintComparison)
where.constraint.constraint.operand_dynamic (OperandDynamicField)
where.constraint.constraint.operand_static (OperandStaticLiteral)
HERE
        , $res);
    }

    public function testGetNode()
    {
        // test field of 2nd part of and statement
        $node = $this->qbTester->getNode(
            'where[0].constraint[0].constraint[1].operand_dynamic'
);
        $this->assertInstanceOf(
            'Doctrine\ODM\PHPCR\Query\Builder\OperandDynamicField', $node
        );
        $this->assertEquals('a', $node->getAlias());
        $this->assertEquals('foo', $node->getField());

        // test literal of 2nd part of and statement
        $node = $this->qbTester->getNode(
            'where[0].constraint[0].constraint[1].operand_static'
        );
        $this->assertInstanceOf(
            'Doctrine\ODM\PHPCR\Query\Builder\OperandStaticLiteral', $node
        );
        $this->assertEquals('Bar', $node->getValue());
    }

    public function testGetAllNodes()
    {
        $count = count($this->qbTester->getAllNodes());
        $this->assertEquals(8, $count);
    }
}
