<?php

namespace Doctrine\Tests\ODM\PHPCR\Query;

use Doctrine\ODM\PHPCR\Query\Query;

/**
 * @group unit
 */
class QueryTest extends \PHPUnit_Framework_Testcase
{
    public function setUp()
    {
        $this->phpcrQuery = $this->getMock('PHPCR\Query\QueryInterface');
        $this->dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->query = new Query($this->phpcrQuery, $this->dm);
    }

    public function testGetSetHydrationMode()
    {
        $this->query->setHydrationMode(Query::HYDRATE_PHPCR_NODE);
        $this->assertEquals(Query::HYDRATE_PHPCR_NODE, $this->query->getHydrationMode());
    }

    public function testGetSetParameters()
    {
        $this->query->setParameters(array(
            'foo' => 'bar',
            'bar' => 'foo',
        ));
        $this->assertEquals(array('foo' => 'bar', 'bar' => 'foo'), $this->query->getParameters());
        $this->query->setParameters(array(
            'boo' => 'far',
            'far' => 'boo',
        ));
        $this->assertEquals(array('boo' => 'far', 'far' => 'boo'), $this->query->getParameters());
    }

    public function testGetSetParameter()
    {
        $this->query->setParameter('foo', 'bar');
        $this->assertEquals('bar', $this->query->getParameter('foo'));

        $this->assertNull($this->query->getParameter('boo'));
    }

    public function testExecute_hydrateNone()
    {
        $this->phpcrQuery->expects($this->once())
            ->method('execute')
            ->will($this->returnValue('ok'));
        $res = $this->query->execute(null, Query::HYDRATE_NONE);
        $this->assertEquals('ok', $res);
    }

    public function testExecute_hydrateDocument()
    {
        $this->dm->expects($this->exactly(2))
            ->method('getDocumentsByQuery')
            ->with($this->phpcrQuery)
            ->will($this->returnValue('ok'));

        $this->query->setDocumentClass('Foo/Bar');
        $res = $this->query->execute();
        $this->assertEquals('ok', $res);

        $res = $this->query->execute(null, Query::HYDRATE_DOCUMENT);
        $this->assertEquals('ok', $res);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Query\QueryException
     */
    public function testExecute_hydrateUnknown()
    {
        $this->query->execute(null, 'unknown_hydration_mode');
    }

    public function testExecute_parameters()
    {
        $this->phpcrQuery->expects($this->at(0))
            ->method('bindValue')
            ->with('foo', 'bar');
        $this->phpcrQuery->expects($this->at(1))
            ->method('bindValue')
            ->with('bar', 'foo');
        $this->query->setDocumentClass('Foo/Bar');
        $this->query->execute(array('foo' => 'bar', 'bar' => 'foo'));
    }

    public function testExecute_maxResults()
    {
        $this->phpcrQuery->expects($this->once())
            ->method('setLimit')
            ->with(5);
        $this->query->setMaxResults(5);
        $this->query->setDocumentClass('Foo/Bar');
        $this->query->execute();
    }

    public function testExecute_firstResult()
    {
        $this->phpcrQuery->expects($this->once())
            ->method('setOffset')
            ->with(5);
        $this->query->setFirstResult(5);
        $this->query->setDocumentClass('Foo/Bar');
        $this->query->execute();
    }

    public function testGetResult()
    {
        $res = $this->query->getResult(Query::HYDRATE_PHPCR_NODE);
        $this->assertEquals(Query::HYDRATE_PHPCR_NODE, $this->query->getHydrationMode());
    }

    public function testGetDocumentResult()
    {
        $res = $this->query->getDocumentResult('Foo/Bar');
        $this->assertEquals(Query::HYDRATE_DOCUMENT, $this->query->getHydrationMode());
        $this->assertEquals('Foo/Bar', $this->query->getDocumentClass());
    }

    public function testGetPhpcrNodeResult()
    {
        $res = $this->query->getPhpcrNodeResult();
        $this->assertEquals(Query::HYDRATE_PHPCR_NODE, $this->query->getHydrationMode());
    }

    public function testGetOneOrNullResult_noResults()
    {
        $this->phpcrQuery->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(array()));
        $res = $this->query->getOneOrNullResult(Query::HYDRATE_PHPCR_NODE);
        $this->assertNull($res);
    }

    public function testGetOneOrNullResult_withOneResult()
    {
        $this->phpcrQuery->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(array('ok1')));
        $res = $this->query->getOneOrNullResult(Query::HYDRATE_PHPCR_NODE);
        $this->assertEquals('ok1', $res);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Query\QueryException
     */
    public function testGetOneOrNullResult_withTwoResults()
    {
        $this->phpcrQuery->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(array('ok1', 'ok2')));
        $this->query->getOneOrNullResult(Query::HYDRATE_PHPCR_NODE);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Query\QueryException
     */
    public function testGetSingleResult_noResult()
    {
        $this->phpcrQuery->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(array()));
        $this->query->getSingleResult(Query::HYDRATE_PHPCR_NODE);
    }

    public function testGetSingleResult_withOneResult()
    {
        $this->phpcrQuery->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(array('ok1')));
        $res = $this->query->getSingleResult(Query::HYDRATE_PHPCR_NODE);
        $this->assertEquals('ok1', $res);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Query\QueryException
     */
    public function testGetSingleResult_withTwoResults()
    {
        $this->phpcrQuery->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(array('ok1', 'ok2')));
        $this->query->getSingleResult(Query::HYDRATE_PHPCR_NODE);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Query\QueryException
     */
    public function testIterate()
    {
        $this->query->iterate();
    }

    public function testGetSetMaxResults()
    {
        $this->query->setMaxResults(5);
        $this->assertEquals(5, $this->query->getMaxResults());
    }

    public function testGetSetFirstResult()
    {
        $this->query->setFirstResult(5);
        $this->assertEquals(5, $this->query->getFirstResult());
    }

    public function testGetStatement()
    {
        $this->phpcrQuery->expects($this->once())
            ->method('getStatement')
            ->will($this->returnValue('foo'));
        $this->assertEquals('foo', $this->query->getStatement());
    }

    public function testGetLanguage()
    {
        $this->phpcrQuery->expects($this->once())
            ->method('getLanguage')
            ->will($this->returnValue('foo'));
        $this->assertEquals('foo', $this->query->getLanguage());
    }

    public function testGetSetDocumentClass()
    {
        $this->query->setDocumentClass('Foo/Bar');
        $this->assertEquals('Foo/Bar', $this->query->getDocumentClass());
    }

    public function testGetPhpcrQuery()
    {
        $query = $this->query->getPhpcrQuery();
        $this->assertSame($this->phpcrQuery, $query);
    }
}
