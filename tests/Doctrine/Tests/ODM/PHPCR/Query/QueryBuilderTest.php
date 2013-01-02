<?php

namespace Doctrine\Tests\ODM\PHPCR\Query;
use Doctrine\ODM\PHPCR\Query\QueryBuilder;

class QueryBuilderTest extends \PHPUnit_Framework_Testcase
{
    public function setUp()
    {
        $this->dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
          ->disableOriginalConstructor()
          ->getMock();
        $this->qomf = $this->getMock('PHPCR\Query\QOM\QueryObjectModelInterface');

        $this->query = $this->getMock('PHPCR\Query\QueryInterface');
        $this->column = $this->getMock('PHPCR\Query\QOM\ColumnInterface');
        $this->selector = $this->getMock('PHPCR\Query\QOM\SelectorInterface');
        $this->constraint1 = $this->getMock('PHPCR\Query\QOM\ConstraintInterface');
        $this->constraint2 = $this->getMock('PHPCR\Query\QOM\ConstraintInterface');
        $this->operand1 = $this->getMock('PHPCR\Query\QOM\DynamicOperandInterface');
        $this->operand2 = $this->getMock('PHPCR\Query\QOM\DynamicOperandInterface');

        $this->qb = new QueryBuilder($this->dm, $this->qb);
    }

    public function testExpr()
    {
        $expr = $this->qb->expr();
        $this->assertInstanceOf('Doctrine\Common\Collections\ExpressionBuilder');
    }

    public function testGetDocumentManager()
    {
        $this->assertSame($this->dm, $this->qb->getDocumentManager());
    }

    public function testGetState()
    {
        $this->assertEquals(QueryBuilder::STATE_CLEAN, $this->qb->getState());
    }

    public function testGetQuery()
    {
        // @todo: Test all cases
        $this->qomf->expects($this->once())
            ->method('createQuery')
            //    ->with(xx, xx, xx, xx) @todo
            ->will($this->returnValue($this->query));
        $this->assertSame($this->qb->getQuery(), $this->query);
    }

    public function testGetSetParameters()
    {
        $ret = $this->qb->setParameters($expected = array('foo' => 'bar', 'bar' => 'foo'));
        $this->assertSame($ret, $this->qb);
        $this->assertEquals($expected, $this->qb->getParameters());
    }

    public function testGetSetParameter()
    {
        $this->qb->setParameter('foo', 'bar');
        $ret = $this->qb->setParameter('bar', 'foo');
        $this->assertSame($ret, $this->qb);
        $this->assertEquals('bar', $this->qb->getParameter('foo'));
        $this->assertEquals('foo', $this->qb->getParameter('bar'));
    }

    public function testGetSetFirstResult()
    {
        $ret = $this->qb->setFirstResult(123);
        $this->assertSame($ret, $this->qb);
        $this->assertEquals(123, $this->qb->getFirstResult());
    }

    public function testGetSetMaxResults()
    {
        $ret = $this->qb->setMaxResults(123);
        $this->assertSame($ret, $this->qb);
        $this->assertEquals(123, $this->qb->getMaxResults());
    }


    public function testAddGetPart()
    {
        // multiple
        $this->qb->addPart('where', 'test');
        $this->assertEquals(array('test'), $this->qb->getPart('where'));
        $this->assertEquals(array(
            'test' => array('test')
        ), $this->qb->getParts());

        $this->qb->addPart('join', 'test');
        $this->assertEquals(array('test'), $this->qb->getPart('join'));

        $this->qb->addPart('orderBy', 'test');
        $this->assertEquals(array('test'), $this->qb->getPart('orderBy'));

        // no append
        $this->qb->addPart('orderBy', 'bar');
        $this->assertEquals(array('bar'), $this->qb->getPart('orderBy'));

        // append
        $this->qb->addPart('orderBy', 'bar', true);
        $this->assertEquals(array('test', 'bar'), $this->qb->getPart('orderBy'));

        // single
        $this->qb->addPart('from', 'test');
        $this->assertEquals('test', $this->qb->getPart('from'));

        $ret = $this->qb->addPart('where', 'test');
        $this->assertEquals('test', $this->qb->getPart('where'));

        // test state
        $this->assertEquals(QueryBuilder::STATE_DIRTY, $this->qb->getState());

        $this->assertSame($ret, $this->qb);
    }

    /**
     * @expectedException Doctrine\ODM\PHPCR\Query\QueryBuilderException
     */
    public function testAddPart_unknown()
    {
        $this->qb->addPart('unknown');
    }

    public function testSelect()
    {
        $this->qomf->expects($this->exactly(2))
            ->method('column')
            ->with('foo', 'bar', 'baz')
            ->will($this->returnValue($this->column));
        $this->qb->select('foo', 'bar', 'baz');
        $ret = $this->qb->select('foo', 'bar', 'baz'); // should overwrite

        $this->assertSame(array($this->column), $this->qb->getPart('select'));
        $this->assertSame($ret, $this->qb);
    }

    public function testAddSelect()
    {
        $this->qomf->expects($this->exactly(2))
            ->method('column')
            ->will($this->returnValue($this->column));
        $this->qb->select('foo', 'bar', 'baz');
        $ret = $this->qb->select('foo', 'bar', 'baz'); // should append
        $this->assertSame(array($this->column, $this->column), $this->qb->getPart('select'));
        $this->assertSame($ret, $this->qb);
    }

    public function testFrom()
    {
        $this->qomf->expects($this->once())
            ->method('selector')
            ->with('nt:foobar', 'selector-name')
            ->will($this->returnValue($this->selector));
        $this->qb->select('nt:foobar', 'selector-name');

        $this->assertSame($this->selector, $this->qb->getPart('from'));
        $this->assertSame($ret, $this->qb);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Query\QueryBuilderException
     */
    public function testJoinWithType_noSource()
    {
        $this->markIncomplete('@todo');
    }

    public function testJoinWithType()
    {
        $this->markIncomplete('@todo');
    }

    public function testJoin()
    {
        $this->markIncomplete('@todo');
    }

    public function testInnerJoin()
    {
        $this->markIncomplete('@todo');
    }

    public function testLeftJoin()
    {
        $this->markIncomplete('@todo');
    }

    public function testWhere()
    {
        $this->qb->where($this->constraint1);
        $this->assertSame($this->constraint1, $this->qb->getPart('where'));
    }

    public function testAndWhere()
    {
        // no existing
        $this->qb->andWhere($this->constraint1);
        $this->assertSame($this->constraint1, $this->qb->getPart('where'));

        // existing
        $this->qomf->expects($this->once())
            ->method('andConstraint')
            ->with($this->constraint1, $this->constraint2);
        $this->qb->andWhere($this->constraint1);
        $this->qb->andWhere($this->constraint2);
    }

    public function testOrWhere()
    {
        // no existing
        $this->qb->orWhere($this->constraint1);
        $this->assertSame($this->constraint1, $this->qb->getPart('where'));

        // existing
        $this->qomf->expects($this->once())
            ->method('orConstraint')
            ->with($this->constraint1, $this->constraint2);
        $this->qb->orWhere($this->constraint1);
        $this->qb->orWhere($this->constraint2);
    }

    public function testOrderBy()
    {
        $this->qomf->expects($this->exactly(2))
            ->method('ascending')
            ->with($this->operand1)
            ->will($this->returnValue('ok'));

        $this->qb->orderBy($this->operand1, 'asc');
        $this->qb->orderBy($this->operand1, 'asc'); // should overwrite
        $this->assertEquals(array('ok'), $this->qb->getPart('orderBy'));

        $this->qomf->expects($this->once())
            ->method('descending')
            ->with($this->operand1)
            ->will($this->returnValue('ok'));

        $this->qb->orderBy($this->operand1, 'desc');
        $this->assertEquals(array('ok'), $this->qb->getPart('orderBy'));
    }

    public function testAddOrderBy()
    {
        $this->qomf->expects($this->at(0))
            ->method('ascending')
            ->with($this->operand1)
            ->will($this->returnValue('ok1'));

        $this->qomf->expects($this->at(1))
            ->method('ascending')
            ->with($this->operand1)
            ->will($this->returnValue('ok2'));

        $this->qb->addOrderBy($this->operand1, 'asc');
        $this->qb->addOrderBy($this->operand2, 'asc'); // should append
        $this->assertEquals(array('ok1', 'ok2'), $this->qb->getPart('orderBy'));
    }

    /**
     * @depends testAddPart
     */
    public function testResetPart()
    {
        $this->qb->addPart('where', 'foobar');
        $this->resetPart('where');
        $this->assertEquals(array(), $this->qb->getPart('where'));
        $this->assertEquals(QueryBuilder::STATE_DIRTY, $this->qb->getState());
    }

    /**
     * @depends testAddPart
     */
    public function testResetParts()
    {
        $this->qb->addPart('where', 'foobar');
        $this->qb->addPart('from', 'foobar');

        // test selective reset
        $this->resetParts(array('from'));
        $this->assertEquals(array('foobar'), $this->qb->getPart('where'));
        $this->assertEquals(null, $this->qb->getPart('from'));

        // test reset all
        $this->qb->addPart('where', 'foobar');
        $this->qb->addPart('from', 'foobar');

        $this->resetParts();
        $this->assertEquals(array(), $this->qb->getPart('from'));
        $this->assertEquals(null, $this->qb->getPart('from'));

        $this->assertEquals(QueryBuilder::STATE_DIRTY, $this->qb->getState());
    }
}
