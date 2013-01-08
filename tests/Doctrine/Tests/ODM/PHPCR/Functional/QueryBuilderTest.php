<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Proxy\Proxy;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Doctrine\Tests\Models\CMS\CmsUser;

/**
 * @group functional
 */
class QueryBuilderTest extends PHPCRFunctionalTestCase
{
    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);

        $user = new CmsUser;
        $user->username = 'dtl';
        $user->name = 'daniel';
        $user->status = 'query_builder';
        $this->dm->persist($user);

        $user = new CmsUser;
        $user->username = 'js';
        $user->name = 'johnsmith';
        $user->status = 'query_builder';
        $this->dm->persist($user);
        $this->dm->flush();
    }

    protected function createQb()
    {
        $qb = $this->dm->createQueryBuilder();
        return $qb;
    }

    protected function getDocs($q)
    {
        $res = $this->dm->getDocumentsByQuery($q);
        return $res;
    }

    public function testComparison()
    {
        $qb = $this->createQb();
        $qb->from('nt:unstructured')->where($qb->expr()->eq('phpcr:class', 'Not Exist'));
        $res = $this->getDocs($qb->getQuery());
        $this->assertCount(0, $res);

        $qb = $this->createQb();
        $qb->from('nt:unstructured')->where(
            $qb->expr()->eq('username', 'dtl')
        );
        $res = $this->getDocs($qb->getQuery());
        $this->assertCount(1, $res);
    }

    public function testComposite()
    {
        $qb = $this->createQb();
        $qb->from('nt:unstructured')->where(
            $qb->expr()->orX(
                $qb->expr()->eq('username', 'dtl'),
                $qb->expr()->eq('username', 'js')
            )
        );
        $res = $this->getDocs($qb->getQuery());
        $this->assertEquals(
            "SELECT s FROM nt:unstructured WHERE username = 'dtl' OR username = 'js'", 
            $qb->__toString()
        );
        $this->assertCount(2, $res); // why does this return only one??

        $qb->andWhere($qb->expr()->eq('name', 'asd'));
        $res = $this->getDocs($qb->getQuery());
        $this->assertEquals(
            "SELECT s FROM nt:unstructured WHERE username = 'dtl' OR username = 'js' AND name = 'asd'", 
            $qb->__toString()
        );
        $this->assertCount(1, $res);

        $qb->orWhere($qb->expr()->eq('name', 'asd'));
        $res = $this->getDocs($qb->getQuery());
        $this->assertEquals(
            "SELECT s FROM nt:unstructured WHERE username = 'dtl' OR username = 'js' AND name = 'asd' OR name = 'asd'", 
            $qb->__toString()
        );
        $this->assertCount(1, $res);
    }

    public function testOrderBy()
    {
        $qb = $this->createQb();
        $qb->from('nt:unstructured');
        $qb->where($qb->expr()->eq('phpcr:class', 'nt:unstructured'));
        $qb->where($qb->expr()->eq('status', 'query_builder'));
        $qb->orderBy('username');
        $res = $this->getDocs($qb->getQuery());
        $this->assertCount(2, $res);
        $this->assertEquals('dtl', $res->first()->username);

        $qb->orderBy('username', 'desc');
        $res = $this->getDocs($qb->getQuery());
        $this->assertCount(2, $res);
        $this->assertEquals('js', $res->first()->username);
    }
}
