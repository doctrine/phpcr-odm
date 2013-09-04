<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsItem;

/**
 * @group functional
 */
class QueryBuilderTest extends PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    protected $dm;

    /**
     * @var \PHPCR\NodeInterface
     */
    protected $node;

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

        $user = new CmsUser;
        $user->username = 'js2';
        $user->name = 'johnsmith';
        $user->status = 'another_johnsmith';
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

    public function testFrom()
    {
        $qb = $this->createQb();
        $qb->from()->document('Doctrine\Tests\Models\CMS\CmsUser', 'a');

        // add where to stop rouge documents that havn't been stored in /functional/ from appearing.
        $qb->where()->eq()->propertyValue('a', 'status')->literal('query_builder')->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(2, $res);
    }

    /**
     * @depends testFrom
     */
    public function testComparison()
    {
        $qb = $this->createQb();
        $qb->from()->document('Doctrine\Tests\Models\CMS\CmsUser', 'a');
        $qb->where()
            ->eq()
                ->propertyValue('a', 'username')
                ->literal('Not Exist')
            ->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(0, $res);

        $qb = $this->createQb();
        $qb->from()->document('Doctrine\Tests\Models\CMS\CmsUser', 'a');
        $qb->where()
            ->eq()
                ->propertyValue('a', 'username')
                ->literal('dtl')
            ->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(1, $res);
    }

    /**
     */
    public function testComposite()
    {
        $qb = $this->createQb();
        $qb->from()->document('Doctrine\Tests\Models\CMS\CmsUser', 'a');
        $qb->where()
            ->orX()
                ->eq()->propertyValue('a', 'username')->literal('dtl')->end()
                ->eq()->propertyValue('a', 'username')->literal('js')->end()
            ->end();

        $res = $qb->getQuery()->execute();

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $query = "SELECT * FROM [nt:unstructured] AS a WHERE ((a.username = 'dtl' OR a.username = 'js') AND (a.[phpcr:class] = 'Doctrine\Tests\Models\CMS\CmsUser' OR a.[phpcr:classparents] = 'Doctrine\Tests\Models\CMS\CmsUser'))";
                break;
            case 'sql':
                $query = "SELECT s FROM nt:unstructured AS a WHERE (a.username = 'dtl' OR a,username = 'js')";
                break;
            default:
                $this->fail('Unexpected query language:'.$qb->getQuery()->getLanguage());
        }

        $this->assertEquals($query, $qb->__toString());
        $this->assertCount(2, $res);

        $qb->andWhere()
            ->eq()->propertyValue('a', 'name')->literal('foobar');

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $query = "SELECT * FROM [nt:unstructured] AS a WHERE (((a.username = 'dtl' OR a.username = 'js') AND a.name = 'foobar') AND (a.[phpcr:class] = 'Doctrine\Tests\Models\CMS\CmsUser' OR a.[phpcr:classparents] = 'Doctrine\Tests\Models\CMS\CmsUser'))";
                break;
            case 'sql':
                $this->markTestIncomplete('Not testing SQL for sql query language');
                break;
            default:
                $this->fail('Unexpected query language:'.$qb->getQuery()->getLanguage());
        }
        $this->assertEquals($query, $qb->__toString());
        $res = $qb->getQuery()->execute();
        $this->assertCount(0, $res);

        $qb->orWhere()
            ->eq()->propertyValue('a', 'name')->literal('johnsmith');

        $res = $qb->getQuery()->execute();

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $query = "SELECT * FROM [nt:unstructured] AS a WHERE ((((a.username = 'dtl' OR a.username = 'js') AND a.name = 'foobar') OR a.name = 'johnsmith') AND (a.[phpcr:class] = 'Doctrine\Tests\Models\CMS\CmsUser' OR a.[phpcr:classparents] = 'Doctrine\Tests\Models\CMS\CmsUser'))";
                break;
            case 'sql':
                $this->markTestIncomplete('Not testing SQL for sql query language');
                break;
            default:
                $this->fail('Unexpected query language:'.$qb->getQuery()->getLanguage());
        }

        $this->assertEquals($query, $qb->__toString());
        $this->assertCount(2, $res);
    }

    /**
     * @depends testFrom
     */
    public function testOrderBy()
    {
        $qb = $this->createQb();
        $qb->from()->document('Doctrine\Tests\Models\CMS\CmsUser', 'a');
        $qb->where()->eq()->propertyValue('a', 'status')->literal('query_builder')->end();
        $qb->orderBy()
            ->ascending()->propertyValue('a', 'username')->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(2, $res);
        $this->assertEquals('dtl', $res->first()->username);

        $qb->orderBy()->descending()->propertyValue('a', 'username')->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(2, $res);
        $this->assertEquals('js', $res->first()->username);
    }

    public function testSelect()
    {
        // select one property
        $qb = $this->createQb();
        $qb->from()->document('Doctrine\Tests\Models\CMS\CmsUser', 'a');
        $qb->select()->property('a', 'username');
        $qb->where()
            ->eq()
                ->propertyValue('a', 'username')
                ->literal('dtl')
            ->end();

        $result = $qb->getQuery()->getPhpcrNodeResult();
        $rows = $result->getRows();
        $this->assertEquals(1, $rows->count());
        $values = $rows->current()->getValues('a');

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $this->assertEquals(array('a'), $result->getSelectorNames());
                $this->assertEquals(array('a.username' => 'dtl'), $values);
                break;
            case 'sql':
                $this->markTestIncomplete('Not testing SQL for sql query language');
                break;
            default:
                $this->fail('Unexpected query language:'.$qb->getQuery()->getLanguage());
        }

        $qb->addSelect()->property('a', 'name');

        $result = $qb->getQuery()->getPhpcrNodeResult();
        $rows = $result->getRows();
        $values = $rows->current()->getValues('a');

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $this->assertEquals(array('a'), $result->getSelectorNames());
                $this->assertEquals(array(
                    'a.username' => 'dtl', 
                    'a.name' => 'daniel',
                ), $values);
                break;
            case 'sql':
                $this->markTestIncomplete('Not testing SQL for sql query language');
                break;
            default:
                $this->fail('Unexpected query language:'.$qb->getQuery()->getLanguage());
        }

        // select overwrite
        $qb->select()->property('a', 'status');
        $result = $qb->getQuery()->getPhpcrNodeResult();
        $rows = $result->getRows();
        $values = $rows->current()->getValues('a');

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $this->assertEquals(array('a'), $result->getSelectorNames());
                $this->assertEquals(array(
                    'a.status' => 'query_builder', 
                ), $values);
                break;
            case 'sql':
                $this->markTestIncomplete('Not testing SQL for sql query language');
                break;
            default:
                $this->fail('Unexpected query language:'.$qb->getQuery()->getLanguage());
        }
    }

    /**
     * @depends testFrom
     */
    public function getTextSearches()
    {
        return array(
            array('name', 'johnsmith', 2),
            array('username', 'dtl', 1),
        );
    }

    /**
     * @dataProvider getTextSearches
     */
    public function testTextSearch($field, $search, $resCount)
    {
        $qb = $this->createQb();
        $qb->from()->document('Doctrine\Tests\Models\CMS\CmsUser', 'a');
        $qb->where()->fullTextSearch('a', $field, $search);
        $q = $qb->getQuery();

        $res = $q->execute();

        $this->assertCount($resCount, $res);
    }

    /**
     * @depends testFrom
     */
    public function testDescendant()
    {
        $qb = $this->createQb();
        $qb->from()->document('Doctrine\Tests\Models\CMS\CmsUser', 'a');
        $qb->where()->descendantDocument('a', '/functional')->end();
        $q = $qb->getQuery();
        $res = $q->execute();
        $this->assertCount(3, $res);
    }

    /**
     * @depends testFrom
     */
    public function testSameNode()
    {
        $qb = $this->createQb();
        $qb->from()->document('Doctrine\Tests\Models\CMS\CmsUser', 'a');
        $qb->where()->sameDocument('a', '/functional/dtl');
        $q = $qb->getQuery();
        $res = $q->execute();
        $this->assertCount(1, $res);
    }
}
