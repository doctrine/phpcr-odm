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
}

