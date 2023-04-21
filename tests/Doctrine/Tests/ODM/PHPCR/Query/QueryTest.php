<?php

namespace Doctrine\Tests\ODM\PHPCR\Query;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Query\NoResultException;
use Doctrine\ODM\PHPCR\Query\Query;
use Doctrine\ODM\PHPCR\Query\QueryException;
use PHPCR\Query\QueryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
class QueryTest extends Testcase
{
    /**
     * @var QueryInterface&MockObject
     */
    private $phpcrQuery;

    /**
     * @var DocumentManager&MockObject
     */
    private $dm;

    /**
     * @var Query&MockObject
     */
    private $query;

    /**
     * @var Query&MockObject
     */
    private $aliasQuery;

    public function setUp(): void
    {
        $this->phpcrQuery = $this->createMock(QueryInterface::class);
        $this->dm = $this->createMock(DocumentManager::class);
        $this->query = new Query($this->phpcrQuery, $this->dm);
        $this->aliasQuery = new Query($this->phpcrQuery, $this->dm, 'a');
    }

    public function testGetSetHydrationMode(): void
    {
        $this->query->setHydrationMode(Query::HYDRATE_PHPCR);
        $this->assertEquals(Query::HYDRATE_PHPCR, $this->query->getHydrationMode());
    }

    public function testGetSetParameters(): void
    {
        $this->query->setParameters([
            'foo' => 'bar',
            'bar' => 'foo',
        ]);
        $this->assertEquals(['foo' => 'bar', 'bar' => 'foo'], $this->query->getParameters());
        $this->query->setParameters([
            'boo' => 'far',
            'far' => 'boo',
        ]);
        $this->assertEquals(['boo' => 'far', 'far' => 'boo'], $this->query->getParameters());
    }

    public function testGetSetParameter(): void
    {
        $this->query->setParameter('foo', 'bar');
        $this->assertEquals('bar', $this->query->getParameter('foo'));

        $this->assertNull($this->query->getParameter('boo'));
    }

    public function testExecuteHydrateNone(): void
    {
        $this->phpcrQuery->expects($this->once())
            ->method('execute')
            ->willReturn(['ok']);
        $res = $this->query->execute(null, Query::HYDRATE_PHPCR);
        $this->assertInstanceOf(ArrayCollection::class, $res);
        $this->assertEquals('ok', $res->first());
    }

    public function testExecuteHydrateDocument(): void
    {
        $this->dm->expects($this->exactly(2))
            ->method('getDocumentsByPhpcrQuery')
            ->with($this->phpcrQuery)
            ->willReturn(['ok']);

        $res = $this->query->execute();
        $this->assertEquals('ok', $res->first());

        $res = $this->query->execute(null, Query::HYDRATE_DOCUMENT);
        $this->assertEquals('ok', $res->first());
    }

    public function testExecuteHydrateDocumentWithAlias(): void
    {
        $this->dm->expects($this->exactly(2))
            ->method('getDocumentsByPhpcrQuery')
            ->with($this->phpcrQuery, null, 'a')
            ->willReturn(['ok']);

        $res = $this->aliasQuery->execute();
        $this->assertEquals('ok', $res->first());

        $res = $this->aliasQuery->execute(null, Query::HYDRATE_DOCUMENT);
        $this->assertEquals('ok', $res->first());
    }

    public function testExecuteHydrateUnknown(): void
    {
        $this->expectException(QueryException::class);
        $this->query->execute(null, 'unknown_hydration_mode');
    }

    public function testExecuteParameters(): void
    {
        $this->phpcrQuery->expects($this->at(0))
            ->method('bindValue')
            ->with('foo', 'bar');
        $this->phpcrQuery->expects($this->at(1))
            ->method('bindValue')
            ->with('bar', 'foo');
        $this->query->execute(['foo' => 'bar', 'bar' => 'foo']);
    }

    public function testExecuteMaxResults(): void
    {
        $this->phpcrQuery->expects($this->once())
            ->method('setLimit')
            ->with(5);
        $this->query->setMaxResults(5);
        $this->query->execute();
    }

    public function testExecuteFirstResult(): void
    {
        $this->phpcrQuery->expects($this->once())
            ->method('setOffset')
            ->with(5);
        $this->query->setFirstResult(5);
        $this->query->execute();
    }

    public function testGetResult(): void
    {
        $res = $this->query->getResult(Query::HYDRATE_PHPCR);
        $this->assertEquals(Query::HYDRATE_PHPCR, $this->query->getHydrationMode());
    }

    public function testGetPhpcrNodeResult(): void
    {
        $res = $this->query->getPhpcrNodeResult();
        $this->assertEquals(Query::HYDRATE_PHPCR, $this->query->getHydrationMode());
    }

    public function testGetOneOrNullResultNoResults(): void
    {
        $this->phpcrQuery->expects($this->once())
            ->method('execute')
            ->willReturn([]);
        $res = $this->query->getOneOrNullResult(Query::HYDRATE_PHPCR);
        $this->assertNull($res);
    }

    public function testGetOneOrNullResultWithOneResult(): void
    {
        $this->phpcrQuery->expects($this->once())
            ->method('execute')
            ->willReturn(['ok1']);
        $res = $this->query->getOneOrNullResult(Query::HYDRATE_PHPCR);
        $this->assertEquals('ok1', $res);
    }

    public function testGetOneOrNullResultWithTwoResults(): void
    {
        $this->phpcrQuery->expects($this->once())
            ->method('execute')
            ->willReturn(['ok1', 'ok2']);

        $this->expectException(QueryException::class);
        $this->query->getOneOrNullResult(Query::HYDRATE_PHPCR);
    }

    public function testGetSingleResultNoResult(): void
    {
        $this->phpcrQuery->expects($this->once())
            ->method('execute')
            ->willReturn([]);

        $this->expectException(NoResultException::class);
        $this->query->getSingleResult(Query::HYDRATE_PHPCR);
    }

    public function testGetSingleResultWithOneResult(): void
    {
        $this->phpcrQuery->expects($this->once())
            ->method('execute')
            ->willReturn(['ok1']);
        $res = $this->query->getSingleResult(Query::HYDRATE_PHPCR);
        $this->assertEquals('ok1', $res);
    }

    public function testGetSingleResultWithTwoResults(): void
    {
        $this->phpcrQuery->expects($this->once())
            ->method('execute')
            ->willReturn(['ok1', 'ok2']);
        $this->expectException(QueryException::class);
        $this->query->getSingleResult(Query::HYDRATE_PHPCR);
    }

    public function testIterate(): void
    {
        $this->expectException(QueryException::class);
        $this->query->iterate();
    }

    public function testGetSetMaxResults(): void
    {
        $this->query->setMaxResults(5);
        $this->assertEquals(5, $this->query->getMaxResults());
    }

    public function testGetSetFirstResult(): void
    {
        $this->query->setFirstResult(5);
        $this->assertEquals(5, $this->query->getFirstResult());
    }

    public function testGetStatement(): void
    {
        $this->phpcrQuery->expects($this->once())
            ->method('getStatement')
            ->willReturn('foo');
        $this->assertEquals('foo', $this->query->getStatement());
    }

    public function testGetLanguage(): void
    {
        $this->phpcrQuery->expects($this->once())
            ->method('getLanguage')
            ->willReturn('foo');
        $this->assertEquals('foo', $this->query->getLanguage());
    }

    public function testGetPhpcrQuery(): void
    {
        $query = $this->query->getPhpcrQuery();
        $this->assertSame($this->phpcrQuery, $query);
    }

    public function testSetDocumentClass(): void
    {
        $this->dm->expects($this->once())
            ->method('getDocumentsByPhpcrQuery')
            ->with($this->phpcrQuery, 'Foo/Bar');
        $this->query->setDocumentClass('Foo/Bar');
        $this->query->execute();
    }
}
