<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Proxy\Proxy;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsItem;

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

        $item = new CmsItem;
        $item->name = 'johnsmith';
        $item->id = '/functional/item1';
        $this->dm->persist($item);
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
        $res = $qb->getQuery()->execute();
        $this->assertCount(0, $res);

        $qb = $this->createQb();
        $qb->nodeType('nt:unstructured')->where(
            $qb->expr()->eq('username', 'dtl')
        );
        $res = $qb->getQuery()->execute();
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
        $res = $qb->getQuery()->execute();
        $this->assertEquals(
            "SELECT s FROM nt:unstructured WHERE (username = 'dtl' OR username = 'js')", 
            $qb->__toString()
        );
        $this->assertCount(2, $res);

        $qb->andWhere($qb->expr()->eq('name', 'foobar'));
        $this->assertEquals(
            "SELECT s FROM nt:unstructured WHERE ((username = 'dtl' OR username = 'js') AND name = 'foobar')", 
            $qb->__toString()
        );
        $res = $qb->getQuery()->execute();
        $this->assertCount(0, $res);

        $qb->orWhere($qb->expr()->eq('name', 'johnsmith'));
        $res = $qb->getQuery()->execute();
        $this->assertEquals(
            "SELECT s FROM nt:unstructured WHERE (((username = 'dtl' OR username = 'js') AND name = 'foobar') OR name = 'johnsmith')", 
            $qb->__toString()
        );
        $this->assertCount(2, $res);
    }

    public function testOrderBy()
    {
        $qb = $this->createQb();
        $qb->nodeType('nt:unstructured');
        $qb->where($qb->expr()->eq('phpcr:class', 'nt:unstructured'));
        $qb->where($qb->expr()->eq('status', 'query_builder'));
        $qb->orderBy('username');
        $res = $qb->getQuery()->execute();
        $this->assertCount(2, $res);
        $this->assertEquals('dtl', $res->first()->username);

        $qb->orderBy('username', 'desc');
        $res = $qb->getQuery()->execute();
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
        $rows = $qb->getQuery()->getPhpcrNodeResult()->getRows();
        $this->assertEquals(1, $rows->count());
        $values = $rows->current()->getValues();
        $this->assertEquals(array('s.username' => 'dtl'), $values);

        // select two properties
        $qb->addSelect('name');
        $rows = $qb->getQuery()->getPhpcrNodeResult()->getRows();
        $values = $rows->current()->getValues();

        $this->assertEquals(array(
            's.username' => 'dtl',
            's.name' => 'daniel'
        ), $values);

        // select overwrite
        $qb->select('status');
        $rows = $qb->getQuery()->getPhpcrNodeResult()->getRows();
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
        $res = $qb->getQuery()->execute();
        $this->assertCount(2, $res);
    }

    public function testFrom_all()
    {
        $qb = $this->createQb();

        // add where to stop rouge documents that havn't been stored in /functional/ from appearing.
        $qb->where($qb->expr()->eq('name', 'johnsmith'));
        $res = $qb->getQuery()->execute();
        $this->assertCount(2, $res);

        $fqns = array(
            get_class($res->current()),
            get_class($res->next()),
        );

        $this->assertContains('Doctrine\Tests\Models\CMS\CmsUser', $fqns);
        $this->assertContains('Doctrine\Tests\Models\CMS\CmsItem', $fqns);
    }

    public function getTextSearches()
    {
        return array(
            array('name', 'johnsmith', 2), 
            array(null, 'johnsmith', 2),
            array('username', 'dtl', 1), 
        );
    }

    /**
     * @dataProvider getTextSearches
     */
    public function testTextSearch($field, $search, $resCount)
    {
        $qb = $this->createQb();
        $qb->where($qb->expr()->textSearch($field, $search));
        $q = $qb->getQuery();

        $where = $qb->getPart('where');

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Query\Expression\TextSearch', $where);
        $this->assertEquals($field, $where->getField());
        $this->assertEquals($search, $where->getSearch());

        $res = $q->execute();

        $this->assertCount($resCount, $res);
    }

    public function testDescendant()
    {
        $qb = $this->createQb();
        $qb->where($qb->expr()->descendant('/functional'));
        $q = $qb->getQuery();

        $where = $qb->getPart('where');

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Query\Expression\Descendant', $where);
        $this->assertEquals('/functional', $where->getPath());

        $res = $q->execute();
        $this->assertCount(3, $res);
    }
}
