<?php

namespace Doctrine\Tests\ODM\PHPCR\Tools\Test;

use Doctrine\ODM\PHPCR\Query\Builder\OperandDynamicField;
use Doctrine\ODM\PHPCR\Query\Builder\OperandStaticLiteral;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Tools\Test\QueryBuilderTester;
use PHPUnit\Framework\TestCase;

class QueryBuilderTesterTest extends TestCase
{
    private $qb;

    /**
     * @var QueryBuilderTester
     */
    private $qbTester;

    public function setUp(): void
    {
        $this->qb = new QueryBuilder();
        $this->qb->where()->andX()
            ->eq()->field('a.foo')->literal('Foo')->end()
            ->eq()->field('a.foo')->literal('Bar');

        $this->qbTester = new QueryBuilderTester($this->qb);
    }

    public function testDumpPaths(): void
    {
        $res = $this->qbTester->dumpPaths();
        $this->assertEquals(<<<'HERE'
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

    public function testGetNode(): void
    {
        // test field of 2nd part of and statement
        $node = $this->qbTester->getNode(
            'where[0].constraint[0].constraint[1].operand_dynamic'
        );
        $this->assertInstanceOf(OperandDynamicField::class, $node);
        $this->assertEquals('a', $node->getAlias());
        $this->assertEquals('foo', $node->getField());

        // test literal of 2nd part of and statement
        $node = $this->qbTester->getNode(
            'where[0].constraint[0].constraint[1].operand_static'
        );
        $this->assertInstanceOf(OperandStaticLiteral::class, $node);
        $this->assertEquals('Bar', $node->getValue());
    }

    public function testGetAllNodes(): void
    {
        $this->assertCount(8, $this->qbTester->getAllNodes());
    }
}
