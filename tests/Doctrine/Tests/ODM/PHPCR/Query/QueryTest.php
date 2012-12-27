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

    public function testMethods()
    {
        $this->phpcrQuery->expects($this->once())
            ->method('bindValue')
            ->with('foo', 'bar');
        $this->query->bindValue('foo', 'bar');

        $this->dm->expects($this->once())
            ->method('getDocumentsByQuery')
            ->with($this->phpcrQuery);
        $this->query->getResults();

        $this->phpcrQuery->expects($this->once())
            ->method('execute');
        $this->query->execute();

        $this->phpcrQuery->expects($this->once())
            ->method('getBindVariableNames');
        $this->query->getBindVariableNames();

        $this->phpcrQuery->expects($this->once())
            ->method('setLimit')
            ->with(5);
        $this->query->setLimit(5);

        $this->phpcrQuery->expects($this->once())
            ->method('setOffset')
            ->with(1);
        $this->query->setOffset(1);

        $this->phpcrQuery->expects($this->once())
            ->method('getStatement');
        $this->query->getStatement();

        $this->phpcrQuery->expects($this->once())
            ->method('getLanguage');
        $this->query->getLanguage();

        $this->phpcrQuery->expects($this->once())
            ->method('getStoredQueryPath');
        $this->query->getStoredQueryPath();

        $this->phpcrQuery->expects($this->once())
            ->method('storeAsNode')
            ->with('/');
        $this->query->storeAsNode('/');
    }
}

