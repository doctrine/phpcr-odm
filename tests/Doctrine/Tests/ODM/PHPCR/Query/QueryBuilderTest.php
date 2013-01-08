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
        $this->qomf = $this->getMock('PHPCR\Query\QOM\QueryObjectModelFactoryInterface');

        $this->query = $this->getMock('PHPCR\Query\QueryInterface');
        $this->column = $this->getMock('PHPCR\Query\QOM\ColumnInterface');
        $this->selector = $this->getMock('PHPCR\Query\QOM\SelectorInterface');
        $this->comparison1 = $this->getMockBuilder('Doctrine\Common\Collections\Expr\Comparison')
            ->disableOriginalConstructor()
            ->getMock();
        $this->comparison2 = $this->getMockBuilder('Doctrine\Common\Collections\Expr\Comparison')
            ->disableOriginalConstructor()
            ->getMock();
        $this->operand1 = $this->getMock('PHPCR\Query\QOM\DynamicOperandInterface');
        $this->operand2 = $this->getMock('PHPCR\Query\QOM\DynamicOperandInterface');

        $this->qb = new QueryBuilder($this->dm, $this->qomf);
    }

    public function testGetType()
    {
        $type = $this->qb->getType();
        $this->assertEquals(QueryBuilder::TYPE_SELECT, $type);
    }

    public function testExpr()
    {
        $expr = $this->qb->expr();
        $this->assertInstanceOf('Doctrine\Common\Collections\ExpressionBuilder', $expr);
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
        $this->qomf->expects($this->once())
            ->method('selector')
            ->will($this->returnValue($this->selector));
        $this->qb->from('nt:unstructured');
        $this->qomf->expects($this->once())
            ->method('createQuery')
            ->will($this->returnValue($this->query));
        $this->assertSame($this->qb->getQuery(), $this->query);
    }

    /**
     * @expectedException Doctrine\ODM\PHPCR\Query\QueryBuilderException
     */
    public function testGetQuery_noSource()
    {
        $this->assertSame($this->qb->getQuery(), $this->query);
    }

    public function testGetSetParameters()
    {
        $this->markTestSkipped('Not yet supported');

        $ret = $this->qb->setParameters($expected = array('foo' => 'bar', 'bar' => 'foo'));
        $this->assertSame($ret, $this->qb);
        $this->assertEquals($expected, $this->qb->getParameters());
    }

    public function testGetSetParameter()
    {
        $this->markTestSkipped('Not yet supported');

        $this->qb->setParameter('foo', 'bar');
        $ret = $this->qb->setParameter('bar', 'foo');
        $this->assertSame($ret, $this->qb);
        $this->assertEquals('bar', $this->qb->getParameter('foo'));
        $this->assertEquals('foo', $this->qb->getParameter('bar'));
        $this->assertNull($this->qb->getParameter('unknown'));
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


    public function testAddPart()
    {
        // multiple
        $this->qb->add('where', 'test');
        $this->assertEquals('test', $this->qb->getPart('where'));
        $this->assertEquals(array(
            'select' => array(),
            'from' => null,
            'join' => array(),
            'where' => 'test',
            'orderBy' => array(),
        ), $this->qb->getParts());

        $this->qb->add('join', 'test');
        $this->assertEquals(array('test'), $this->qb->getPart('join'));

        $this->qb->add('orderBy', 'test');
        $this->assertEquals(array('test'), $this->qb->getPart('orderBy'));

        // no append
        $this->qb->add('orderBy', 'test');
        $this->assertEquals(array('test'), $this->qb->getPart('orderBy'));

        // append
        $this->qb->add('orderBy', 'bar', true);
        $this->assertEquals(array('test', 'bar'), $this->qb->getPart('orderBy'));

        // single
        $this->qb->add('from', 'test');
        $this->assertEquals('test', $this->qb->getPart('from'));

        $ret = $this->qb->add('where', 'test');
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
        $this->qb->add('unknown', 'asd');
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
        $ret = $this->qb->addSelect('foo', 'bar', 'baz'); // should append
        $this->assertSame(array($this->column, $this->column), $this->qb->getPart('select'));
        $this->assertSame($ret, $this->qb);
    }

    public function testFrom()
    {
        $this->qomf->expects($this->once())
            ->method('selector')
            ->with('nt:foobar', 'selector-name')
            ->will($this->returnValue($this->selector));
        $ret = $this->qb->from('nt:foobar', 'selector-name');

        $this->assertSame($this->selector, $this->qb->getPart('from'));
        $this->assertSame($ret, $this->qb);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Query\QueryBuilderException
     */
    public function testJoinWithType_noSource()
    {
        $this->markTestIncomplete('@todo');
    }

    public function testJoinWithType()
    {
        $this->markTestIncomplete('@todo');
    }

    public function testJoin()
    {
        $this->markTestIncomplete('@todo');
    }

    public function testInnerJoin()
    {
        $this->markTestIncomplete('@todo');
    }

    public function testLeftJoin()
    {
        $this->markTestIncomplete('@todo');
    }

    public function testWhere()
    {
        $this->qb->where($this->comparison1);
        $this->assertSame($this->comparison1, $this->qb->getPart('where'));
    }

    public function testAndWhere()
    {
        // no existing
        $this->qb->andWhere($this->comparison1);
        $this->assertSame($this->comparison1, $this->qb->getPart('where'));

        // existing
        $this->qb->andWhere($this->comparison1);
        $this->qb->andWhere($this->comparison2);
        $this->assertInstanceOf('Doctrine\Common\Collections\Expr\CompositeExpression', $this->qb->getPart('where'));
    }

    public function testOrWhere()
    {
        // no existing
        $this->qb->orWhere($this->comparison1);
        $this->assertSame($this->comparison1, $this->qb->getPart('where'));

        // existing
        $this->qb->orWhere($this->comparison1);
        $this->qb->orWhere($this->comparison2);
        $this->assertInstanceOf('Doctrine\Common\Collections\Expr\CompositeExpression', $this->qb->getPart('where'));
    }

    public function testOrderBy()
    {
        $this->qomf->expects($this->any())
            ->method('propertyValue')
            ->will($this->returnValue($this->operand1));

        $this->qomf->expects($this->any())
            ->method('ascending')
            ->with($this->operand1)
            ->will($this->returnValue('ok'));

        $this->qb->orderBy('prop1', 'asc');
        $this->qb->orderBy('prop2', 'asc'); // should overwrite
        $this->assertEquals(array('ok'), $this->qb->getPart('orderBy'));

        $this->qomf->expects($this->once())
            ->method('descending')
            ->with($this->operand1)
            ->will($this->returnValue('ok'));

        $this->qb->orderBy('prop1', 'desc');
        $this->assertEquals(array('ok'), $this->qb->getPart('orderBy'));

        $this->qb->orderBy(array('foo', 'bar'));
    }

    public function testAddOrderBy()
    {
        $this->qomf->expects($this->at(0))
            ->method('propertyValue')
            ->will($this->returnValue($this->operand1));

        $this->qomf->expects($this->at(1))
            ->method('ascending')
            ->with($this->operand1)
            ->will($this->returnValue('ok1'));

        $this->qomf->expects($this->at(2))
            ->method('propertyValue')
            ->will($this->returnValue($this->operand2));

        $this->qomf->expects($this->at(3))
            ->method('ascending')
            ->with($this->operand2)
            ->will($this->returnValue('ok2'));

        $this->qb->addOrderBy('prop1', 'asc');
        $this->qb->addOrderBy('prop2', 'asc'); // should append
        $this->assertEquals(array('ok1', 'ok2'), $this->qb->getPart('orderBy'));
    }

    /**
     * @depends testAddPart
     */
    public function testResetPart()
    {
        $this->qb->add('where', 'foobar');
        $this->qb->resetPart('where');
        $this->assertEquals(null, $this->qb->getPart('where'));
        $this->assertEquals(QueryBuilder::STATE_DIRTY, $this->qb->getState());
    }

    /**
     * @depends testAddPart
     */
    public function testResetParts()
    {
        $this->qb->add('where', 'foobar');
        $this->qb->add('from', 'foobar');

        // test selective reset
        $this->qb->resetParts(array('from'));
        $this->assertEquals('foobar', $this->qb->getPart('where'));
        $this->assertEquals(null, $this->qb->getPart('from'));

        // test reset all
        $this->qb->add('where', 'foobar');
        $this->qb->add('from', 'foobar');

        $this->qb->resetParts();
        $this->assertEquals(null, $this->qb->getPart('from'));
        $this->assertEquals(null, $this->qb->getPart('from'));

        $this->assertEquals(QueryBuilder::STATE_DIRTY, $this->qb->getState());
    }
}
