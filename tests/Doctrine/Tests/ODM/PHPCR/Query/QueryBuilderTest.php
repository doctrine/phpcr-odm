<?php

namespace Doctrine\Tests\ODM\PHPCR\Query;

use Doctrine\ODM\PHPCR\Query\QueryBuilder;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    protected $qf;

    public function setUp()
    {
        $this->qf = $this->getMock('PHPCR\Query\QOM\QueryObjectModelFactoryInterface', array(), array());
    }

    public function testSetFirstResult()
    {
        $qb = new QueryBuilder($this->qf);
        $qb->setFirstResult(15);
        $this->assertEquals(15, $qb->getFirstResult());
    }

    public function testSetMaxResults()
    {
        $qb = new QueryBuilder($this->qf);
        $qb->setMaxResults(15);
        $this->assertEquals(15, $qb->getMaxResults());
    }

    public function testAddOrderBy()
    {
        $dynamicOperand = $this->getMock('PHPCR\Query\QOM\DynamicOperandInterface', array(), array());
        $qb = new QueryBuilder($this->qf);
        $qb->addOrderBy($dynamicOperand, 'ASC');
        $qb->addOrderBy($dynamicOperand, 'DESC');
        $this->assertEquals(2, count($qb->getOrderings()));
        $orderings = $qb->getOrderings();
    }

    public function testOrderBy()
    {
        $dynamicOperand = $this->getMock('PHPCR\Query\QOM\DynamicOperandInterface', array(), array());
        $qb = new QueryBuilder($this->qf);
        $qb->orderBy($dynamicOperand, 'ASC');
        $qb->orderBy($dynamicOperand, 'ASC');
        $this->assertEquals(1, count($qb->getOrderings()));
    }

    public function testOrderAscending()
    {
        $dynamicOperand = $this->getMock('PHPCR\Query\QOM\DynamicOperandInterface', array(), array());
        $this->qf->expects($this->once())
                 ->method('ascending')
                 ->with($this->equalTo($dynamicOperand));
        $qb = new QueryBuilder($this->qf);
        $qb->addOrderBy($dynamicOperand, 'ASC');
    }

    public function testOrderDescending()
    {
        $dynamicOperand = $this->getMock('PHPCR\Query\QOM\DynamicOperandInterface', array(), array());
        $this->qf->expects($this->once())
                 ->method('descending')
                 ->with($this->equalTo($dynamicOperand));
        $qb = new QueryBuilder($this->qf);
        $qb->addOrderBy($dynamicOperand, null);
    }

    public function testWhere()
    {
        $constraint = $this->getMock('PHPCR\Query\QOM\ConstraintInterface', array(), array());
        $qb = new QueryBuilder($this->qf);
        $qb->where($constraint);
        $this->assertEquals($constraint, $qb->getConstraint());
    }

    public function testAndWhere()
    {
        $dynamicOperand = $this->getMock('PHPCR\Query\QOM\ConstraintInterface', array(), array());
        $this->qf = $this->getMock('PHPCR\Query\QOM\QueryObjectModelFactoryInterface', array(), array());
        $this->qf->expects($this->once())
                 ->method('andConstraint');

        $constraint1 = $this->getMock('PHPCR\Query\QOM\ConstraintInterface', array(), array());
        $constraint2 = $this->getMock('PHPCR\Query\QOM\ConstraintInterface', array(), array());
        $qb = new QueryBuilder($this->qf);
        $qb->where($constraint1);
        $qb->andWhere($constraint2);
    }

    public function testOrWhere()
    {
        $dynamicOperand = $this->getMock('PHPCR\Query\QOM\ConstraintInterface', array(), array());
        $this->qf = $this->getMock('PHPCR\Query\QOM\QueryObjectModelFactoryInterface', array(), array());
        $this->qf->expects($this->once())
                 ->method('orConstraint');

        $constraint1 = $this->getMock('PHPCR\Query\QOM\ConstraintInterface', array(), array());
        $constraint2 = $this->getMock('PHPCR\Query\QOM\ConstraintInterface', array(), array());
        $qb = new QueryBuilder($this->qf);
        $qb->where($constraint1);
        $qb->orWhere($constraint2);
    }

    public function testSelect()
    {
        $qb = new QueryBuilder($this->qf);
        $this->assertEquals(0, count($qb->getColumns()));
        $qb->select('propertyName', 'columnName', 'selectorName');
        $this->assertEquals(1, count($qb->getColumns()));
        $qb->select('propertyName', 'columnName', 'selectorName');
        $this->assertEquals(1, count($qb->getColumns()));
    }

    public function testAddSelect()
    {
        $qb = new QueryBuilder($this->qf);
        $this->assertEquals(0, count($qb->getColumns()));
        $qb->addSelect('propertyName', 'columnName', 'selectorName');
        $this->assertEquals(1, count($qb->getColumns()));
        $qb->addSelect('propertyName', 'columnName', 'selectorName');
        $this->assertEquals(2, count($qb->getColumns()));
    }

    public function testFrom()
    {
        $source = $this->getMock('PHPCR\Query\QOM\SourceInterface', array(), array());
        $qb = new QueryBuilder($this->qf);
        $qb->from($source);
        $this->assertEquals($source, $qb->getSource());
    }

    public function testInvalidJoin()
    {
        $this->setExpectedException('\RuntimeException');
        $source = $this->getMock('PHPCR\Query\QOM\SourceInterface', array(), array());
        $joinCondition = $this->getMock('PHPCR\Query\QOM\SameNodeJoinConditionInterface', array(), array());
        $qb = new QueryBuilder($this->qf);
        $qb->join($source, $joinCondition);
    }

    public function testJoin()
    {
        $source1 = $this->getMock('PHPCR\Query\QOM\SourceInterface', array(), array());
        $source2= $this->getMock('PHPCR\Query\QOM\SourceInterface', array(), array());
        $joinCondition = $this->getMock('PHPCR\Query\QOM\SameNodeJoinConditionInterface', array(), array());
        $qb = new QueryBuilder($this->qf);
        $qb->from($source1);
        $qb->join($source2, $joinCondition);
    }

    public function testRightJoin()
    {
        $source1 = $this->getMock('PHPCR\Query\QOM\SourceInterface', array(), array());
        $source2= $this->getMock('PHPCR\Query\QOM\SourceInterface', array(), array());
        $joinCondition = $this->getMock('PHPCR\Query\QOM\SameNodeJoinConditionInterface', array(), array());
        $this->qf->expects($this->once())
                 ->method('join')
                 ->with($source1, $source2, $this->equalTo(\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_JOIN_TYPE_RIGHT_OUTER), $joinCondition);
        $qb = new QueryBuilder($this->qf);
        $qb->from($source1);
        $qb->rightJoin($source2, $joinCondition);
    }

    public function testLeftJoin()
    {
        $source1 = $this->getMock('PHPCR\Query\QOM\SourceInterface', array(), array());
        $source2= $this->getMock('PHPCR\Query\QOM\SourceInterface', array(), array());
        $joinCondition = $this->getMock('PHPCR\Query\QOM\SameNodeJoinConditionInterface', array(), array());
        $this->qf->expects($this->once())
                 ->method('join')
                 ->with($source1, $source2, $this->equalTo(\PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_JOIN_TYPE_LEFT_OUTER), $joinCondition);
        $qb = new QueryBuilder($this->qf);
        $qb->from($source1);
        $qb->leftJoin($source2, $joinCondition);
    }

    public function testGetQuery()
    {
        $source = $this->getMock('PHPCR\Query\QOM\SourceInterface', array(), array());
        $constraint = $this->getMock('PHPCR\Query\QOM\ConstraintInterface', array(), array());
        $qb = new QueryBuilder($this->qf);
        $qb->from($source);
        $qb->where($constraint);
        $this->qf->expects($this->once())
                 ->method('createQuery')
                 ->will($this->returnValue("true"));
        $qb->getQuery();
        //state is clean, query is stored
        $qb->getQuery();
    }
}
