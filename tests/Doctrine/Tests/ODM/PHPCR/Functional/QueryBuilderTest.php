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
        $res = $this->dm->getDocumentsByPhpcrQuery($q);
        return $res;
    }

    public function testComparison()
    {
        $qb = $this->createQb();
        $qb->nodeType('nt:unstructured')->where($qb->expr()->eq('phpcr:class', 'Not Exist'));
        $res = $this->getDocs($qb->getQuery());
        $this->assertCount(0, $res);

        $qb = $this->createQb();
        $qb->nodeType('nt:unstructured')->where(
            $qb->expr()->eq('username', 'dtl')
        );
        $res = $this->getDocs($qb->getQuery());
        $this->assertCount(1, $res);
    }

    public function testComposite()
    {
        $qb = $this->createQb();
        $qb->nodeType('nt:unstructured')->where(
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
        $this->assertCount(2, $res);

        $qb->andWhere($qb->expr()->eq('name', 'foobar'));
        $res = $this->getDocs($qb->getQuery());
        $this->assertEquals(
            "SELECT s FROM nt:unstructured WHERE username = 'dtl' OR username = 'js' AND name = 'foobar'", 
            $qb->__toString()
        );
        $this->assertCount(1, $res);

        $qb->orWhere($qb->expr()->eq('name', 'foobar'));
        $res = $this->getDocs($qb->getQuery());
        $this->assertEquals(
            "SELECT s FROM nt:unstructured WHERE username = 'dtl' OR username = 'js' AND name = 'foobar' OR name = 'foobar'", 
            $qb->__toString()
        );
        $this->assertCount(1, $res);
    }

    public function testOrderBy()
    {
        $qb = $this->createQb();
        $qb->nodeType('nt:unstructured');
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

    public function testSelect()
    {
        // select one property
        $qb = $this->createQb();
        $qb->nodeType('nt:unstructured');
        $qb->select('username');
        $qb->where($qb->expr()->eq('username', 'dtl'));
        $rows = $qb->getQuery()->execute()->getRows();
        $this->assertEquals(1, $rows->count());
        $values = $rows->current()->getValues();
        $this->assertEquals(array('s.username' => 'dtl'), $values);

        // select two properties
        $qb->addSelect('name');
        $rows = $qb->getQuery()->execute()->getRows();
        $values = $rows->current()->getValues();

        $this->assertEquals(array(
            's.username' => 'dtl',
            's.name' => 'daniel'
        ), $values);

        // select overwrite
        $qb->select('status');
        $rows = $qb->getQuery()->execute()->getRows();
        $values = $rows->current()->getValues();

        $this->assertEquals(array(
            's.status' => 'query_builder',
        ), $values);
    }

    public function testFrom()
    {
        $qb = $this->createQb();
        $qb->from('Doctrine\Tests\Models\CMS\CmsUser');

        // add where to stop rouge documents that havn't been stored in /functional/ from appearing.
        $qb->where($qb->expr()->eq('status', 'query_builder'));
        $res = $this->getDocs($qb->getQuery());
        $this->assertCount(2, $res);
    }
}
