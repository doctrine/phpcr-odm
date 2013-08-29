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
        $qb->where()
            ->eq()
                ->propertyValue('phpcr:class')
                ->literal('Not Exist')
            ->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(0, $res);

        $qb = $this->createQb();
        $qb->where()
            ->eq()
                ->propertyValue('username')
                ->literal('dtl')
            ->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(1, $res);
    }

    public function testComposite()
    {
        $qb = $this->createQb();
        $qb->where()
            ->orX()
                ->eq()->propertyValue('username')->literal('dtl')->end()
                ->eq()->propertyValue('username')->literal('js')->end()
            ->end();

        $res = $qb->getQuery()->execute();

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $query = "SELECT * FROM [nt:unstructured] WHERE (username = 'dtl' OR username = 'js')";
                break;
            case 'sql':
                $query = "SELECT s FROM nt:unstructured WHERE (username = 'dtl' OR username = 'js')";
                break;
            default:
                $this->fail('Unexpected query language:'.$qb->getQuery()->getLanguage());
        }
        $this->assertEquals($query, $qb->__toString());
        $this->assertCount(2, $res);

        $this->markTestIncomplete('Need to add way to add new where conditions');

        // none of below works
        $qb->andWhere($qb->expr()->eq('name', 'foobar'));
        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $query = "SELECT * FROM [nt:unstructured] WHERE ((username = 'dtl' OR username = 'js') AND name = 'foobar')";
                break;
            case 'sql':
                $query = "SELECT s FROM nt:unstructured WHERE ((username = 'dtl' OR username = 'js') AND name = 'foobar')";
                break;
            default:
                $this->fail('Unexpected query language:'.$qb->getQuery()->getLanguage());
        }
        $this->assertEquals($query, $qb->__toString());
        $res = $qb->getQuery()->execute();
        $this->assertCount(0, $res);

        $qb->orWhere($qb->expr()->eq('name', 'johnsmith'));
        $res = $qb->getQuery()->execute();
        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $query = "SELECT * FROM [nt:unstructured] WHERE (((username = 'dtl' OR username = 'js') AND name = 'foobar') OR name = 'johnsmith')";
                break;
            case 'sql':
                $query = "SELECT s FROM nt:unstructured WHERE (((username = 'dtl' OR username = 'js') AND name = 'foobar') OR name = 'johnsmith')";
                break;
            default:
                $this->fail('Unexpected query language:'.$qb->getQuery()->getLanguage());
        }
        $this->assertEquals($query, $qb->__toString());
        $this->assertCount(2, $res);
    }

    public function testOrderBy()
    {
        $qb = $this->createQb();
        $qb->where()->eq()->propertyValue('status')->literal('query_builder')->end();

        $qb->orderBy()
            ->ascending()->propertyValue('username')->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(2, $res);
        $this->assertEquals('dtl', $res->first()->username);

        $qb->orderBy()->descending()->propertyValue('username')->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(2, $res);
        $this->assertEquals('js', $res->first()->username);
    }

    public function testSelect()
    {
        // select one property
        $qb = $this->createQb();
        $qb->from()->nodeType('nt:unstructured');
        $qb->select()->property('username');
        $qb->where()
            ->eq()
                ->propertyValue('username')
                ->literal('dtl')
            ->end();

        $rows = $qb->getQuery()->getPhpcrNodeResult()->getRows();
        $this->assertEquals(1, $rows->count());
        $values = $rows->current()->getValues();

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $this->assertEquals(array('nt:unstructured.username' => 'dtl', 'nt:unstructured.jcr:primaryType' => 'nt:unstructured'), $values);
                break;
            case 'sql':
                $this->assertEquals(array('s.username' => 'dtl'), $values);
                break;
            default:
                $this->fail('Unexpected query language:'.$qb->getQuery()->getLanguage());
        }

        // select two properties
        $this->markTestIncomplete('todo: cannot currently add additional select columns');

        $qb->addSelect('name');
        $rows = $qb->getQuery()->getPhpcrNodeResult()->getRows();
        $values = $rows->current()->getValues();

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $this->assertEquals(array('nt:unstructured.username' => 'dtl', 'nt:unstructured.name' => 'daniel', 'nt:unstructured.jcr:primaryType' => 'nt:unstructured'), $values);
                break;
            case 'sql':
                $this->assertEquals(array('s.username' => 'dtl', 's.name' => 'daniel' ), $values);
                break;
            default:
                $this->fail('Unexpected query language:'.$qb->getQuery()->getLanguage());
        }

        // select overwrite
        $qb->select('status');
        $rows = $qb->getQuery()->getPhpcrNodeResult()->getRows();
        $values = $rows->current()->getValues();

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $this->assertEquals(array('nt:unstructured.status' => 'query_builder', 'nt:unstructured.jcr:primaryType' => 'nt:unstructured'), $values);
                break;
            case 'sql':
                $this->assertEquals(array('s.status' => 'query_builder'), $values);
                break;
            default:
                $this->fail('Unexpected query language:'.$qb->getQuery()->getLanguage());
        }
    }

    public function testFrom()
    {
        $qb = $this->createQb();
        $qb->from()->document('Doctrine\Tests\Models\CMS\CmsUser');

        // add where to stop rouge documents that havn't been stored in /functional/ from appearing.
        $qb->where()->eq()->propertyValue('status')->literal('query_builder')->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(2, $res);
    }

    public function testFromAll()
    {
        $qb = $this->createQb();

        // add where to stop rouge documents that havn't been stored in /functional/ from appearing.
        $qb->where()
            ->eq()
              ->propertyValue('name')
              ->literal('johnsmith')
            ->end();
              
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
            array('username', 'dtl', 1),
        );
    }

    /**
     * @dataProvider getTextSearches
     */
    public function testTextSearch($field, $search, $resCount)
    {
        $qb = $this->createQb();
        $qb->where()->fullTextSearch($field, $search);
        $q = $qb->getQuery();

        $res = $q->execute();

        $this->assertCount($resCount, $res);
    }

    public function testDescendant()
    {
        $qb = $this->createQb();
        $qb->where()->descendantDocument('/functional')->end();
        $q = $qb->getQuery();

        $where = $qb->getPart('where');

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Query\Expression\Descendant', $where);
        $this->assertEquals('/functional', $where->getPath());

        $res = $q->execute();
        $this->assertCount(3, $res);
    }

    public function testSameNode()
    {
        $qb = $this->createQb();
        $qb->where()->sameDocument('/functional/dtl');
        $q = $qb->getQuery();

        $where = $qb->getPart('where');

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Query\Expression\SameNode', $where);
        $this->assertEquals('/functional/dtl', $where->getPath());

        $res = $q->execute();
        $this->assertCount(1, $res);
    }
}
