<?php

namespace Doctrine\Tests\ODM\PHPCR\Query;
use Doctrine\ODM\PHPCR\Query\QueryBuilder;

/**
 * @group unit
 */
class QueryBuilderTest extends \PHPUnit_Framework_Testcase
{
    public function setUp()
    {
        $this->qomf = $this->getMock('PHPCR\Query\QOM\QueryObjectModelFactoryInterface');
        $this->dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->query = $this->getMock('PHPCR\Query\QueryInterface');
        $this->from = $this->getMock('PHPCR\Query\QOM\SelectorInterface');
        $this->qb = new QueryBuilder($this->qomf, $this->dm);
    }

    public function testGetQuery()
    {
        $this->qb->from($this->from);
        $this->qomf->expects($this->once())
            ->method('createQuery')
            ->will($this->returnValue($this->query));
        $query = $this->qb->getQuery();
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Query\Query', $query);
        $this->assertSame($this->query, $query->getPhpcrQuery());
    }
}
