<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Doctrine\Tests\Models\Blog\User as BlogUser;
use Doctrine\Tests\Models\Blog\Post;
use Doctrine\Tests\Models\Blog\Comment;
use PHPCR\Util\PathHelper;
use PHPCR\Util\NodeHelper;

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
        $session = $this->dm->getPhpcrSession();

        // comment
        // post
        // user
        NodeHelper::createPath($session, '/functional/user');
        NodeHelper::createPath($session, '/functional/post');

        $user = new BlogUser;
        $user->id = '/functional/user/dtl';
        $user->name = 'daniel';
        $user->username = 'dtl';
        $user->status = 'query_builder';
        $this->dm->persist($user);

        $user = new BlogUser;
        $user->id = '/functional/user/js';
        $user->username = 'js';
        $user->name = 'johnsmith';
        $user->status = 'query_builder';
        $this->dm->persist($user);

        $user = new BlogUser;
        $user->id = '/functional/user/js2';
        $user->name = 'johnsmith';
        $user->username = 'js2';
        $user->status = 'another_johnsmith';
        $this->dm->persist($user);

        $post = new Post;
        $post->id = '/functional/post/post_1';
        $post->title = 'Post 2';
        $post->username = 'dtl';
        $this->dm->persist($post);

        $comment1 = new Comment;
        $comment1->id = '/functional/post/post_1/comment_1';
        $comment1->title = 'Comment 1';
        $this->dm->persist($comment1);

        $comment2 = new Comment;
        $comment2->id = '/functional/post/post_1/comment_2';
        $comment2->title = 'Comment 1';
        $this->dm->persist($comment2);

        $reply1 = new Comment;
        $reply1->id = '/functional/post/post_1/comment_1/reply_1';
        $reply1->title = 'Reply to Comment 1';
        $this->dm->persist($reply1);

        $post = new Post;
        $post->id = '/functional/post/post_2';
        $post->title = 'Post 2';
        $post->username = 'dtl';
        $this->dm->persist($post);

        $comment3 = new Comment;
        $comment3->id = '/functional/post/post_2/comment_3';
        $comment3->title = 'Comment 3';
        $this->dm->persist($comment3);

        $post3 = new Post;
        $post3->id = '/functional/post/post_3';
        $post3->title = 'Post 3';
        $post3->username = 'js';
        $this->dm->persist($post3);

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
        $qb->from()->document('Doctrine\Tests\Models\Blog\User', 'a');

        // add where to stop rouge documents that havn't been stored in /functional/ from appearing.
        $qb->where()->eq()->field('a.status')->literal('query_builder')->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(2, $res);
    }

    public function testFromWithAlias()
    {
        $config = $this->dm->getConfiguration();
        $config->addDocumentNamespace('Foobar', 'Doctrine\Tests\Models\Blog');

        $qb = $this->createQb();
        $qb->from()->document('Foobar:User', 'a');
        // add where to stop rouge documents that havn't been stored in /functional/ from appearing.
        $qb->where()->eq()->field('a.status')->literal('query_builder')->end();
        $res = $qb->getQuery()->execute();
        $this->assertCount(2, $res);
    }

    /**
     * @depends testFrom
     */
    public function testComparison()
    {
        $qb = $this->createQb();
        $qb->from('a')->document('Doctrine\Tests\Models\Blog\User', 'a');
        $qb->where()
            ->eq()
                ->field('a.username')
                ->literal('Not Exist')
            ->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(0, $res);

        $qb = $this->createQb();
        $qb->from('a')->document('Doctrine\Tests\Models\Blog\User', 'a');
        $qb->where()
            ->eq()
                ->field('a.username')
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
        $qb->from('a')->document('Doctrine\Tests\Models\Blog\User', 'a');
        $qb->where()
            ->orX()
                ->eq()->field('a.username')->literal('dtl')->end()
                ->eq()->field('a.username')->literal('js')->end()
            ->end();

        $res = $qb->getQuery()->execute();

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $query = "SELECT * FROM [nt:unstructured] AS a WHERE ((a.username = 'dtl' OR a.username = 'js') AND (a.[phpcr:class] = 'Doctrine\Tests\Models\Blog\User' OR a.[phpcr:classparents] = 'Doctrine\Tests\Models\Blog\User'))";
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
            ->eq()->field('a.name')->literal('foobar');

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $query = "SELECT * FROM [nt:unstructured] AS a WHERE (((a.username = 'dtl' OR a.username = 'js') AND a.name = 'foobar') AND (a.[phpcr:class] = 'Doctrine\Tests\Models\Blog\User' OR a.[phpcr:classparents] = 'Doctrine\Tests\Models\Blog\User'))";
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
            ->eq()->field('a.name')->literal('johnsmith');

        $res = $qb->getQuery()->execute();

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $query = "SELECT * FROM [nt:unstructured] AS a WHERE ((((a.username = 'dtl' OR a.username = 'js') AND a.name = 'foobar') OR a.name = 'johnsmith') AND (a.[phpcr:class] = 'Doctrine\Tests\Models\Blog\User' OR a.[phpcr:classparents] = 'Doctrine\Tests\Models\Blog\User'))";
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
        $qb->from('a')->document('Doctrine\Tests\Models\Blog\User', 'a');
        $qb->where()->eq()->field('a.status')->literal('query_builder')->end();
        $qb->orderBy()->asc()->field('a.username')->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(2, $res);
        $this->assertEquals('dtl', $res->first()->username);

        $qb->orderBy()->desc()->field('a.username')->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(2, $res);
        $this->assertEquals('js', $res->first()->username);
    }

    /**
     * @depends testFrom
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testOrderByNonSimpleField()
    {
        $qb = $this->createQb();
        $qb->from('a')->document('Doctrine\Tests\Models\Blog\User', 'a');
        $qb->orderBy()->asc()->localname('a.username')->end();

        $qb->getQuery()->execute();
    }

    /**
     * Removes jcr:primaryType from row values,
     * Jackrabbit does not return this, but doctrinedbal does.
     */
    protected function cleanValues($values)
    {
        if (isset($values['a.jcr:primaryType'])) {
            unset($values['a.jcr:primaryType']);
        }

        return $values;
    }

    public function testSelect()
    {
        // select one property
        $qb = $this->createQb();
        $qb->from('a')->document('Doctrine\Tests\Models\Blog\User', 'a');
        $qb->select()->field('a.username');
        $qb->where()
            ->eq()
                ->field('a.username')
                ->literal('dtl')
            ->end();

        $result = $qb->getQuery()->getPhpcrNodeResult();
        $rows = $result->getRows();
        $values = $rows->current()->getValues('a');
        $values = $this->cleanValues($values);

        $this->assertEquals(1, $rows->count());

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

        $qb->addSelect()->field('a.name');

        $result = $qb->getQuery()->getPhpcrNodeResult();
        $rows = $result->getRows();
        $values = $rows->current()->getValues('a');
        $values = $this->cleanValues($values);

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
        $qb->select()->field('a.status');
        $rows = $qb->getQuery()->getPhpcrNodeResult()->getRows();
        $values = $rows->current()->getValues();
        $values = $this->cleanValues($values);

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
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
        $qb->from('a')->document('Doctrine\Tests\Models\Blog\User', 'a');
        $qb->where()->fullTextSearch('a.'.$field, $search);
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
        $qb->from('a')->document('Doctrine\Tests\Models\Blog\User', 'a');
        $qb->where()->descendant('/functional', 'a')->end();
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
        $qb->from('a')->document('Doctrine\Tests\Models\Blog\User', 'a');
        $qb->where()->same('/functional/user/dtl', 'a');
        $q = $qb->getQuery();
        $res = $q->execute();

        $this->assertCount(1, $res);
    }

    /**
     * @expectedException InvalidArgumentException 
     * @expectedExceptionMessage Alias name "a" is not known
     */
    public function testConditionWithNonExistingAlias()
    {
        $qb = $this->createQb();
        $qb->from('a')->document('Doctrine\Tests\Models\Blog\User', 'b');
        $qb->where()->descendant('/functional', 'a')->end();
        $q = $qb->getQuery();
        $res = $q->execute();
        $this->assertCount(3, $res);
    }
}
