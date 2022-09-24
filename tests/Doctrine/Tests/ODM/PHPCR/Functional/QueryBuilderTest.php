<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\Tests\Models\Blog\Comment;
use Doctrine\Tests\Models\Blog\Post;
use Doctrine\Tests\Models\Blog\User as BlogUser;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\Util\NodeHelper;

/**
 * @group functional
 */
class QueryBuilderTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager();
        $this->resetFunctionalNode($this->dm);
        $session = $this->dm->getPhpcrSession();

        // comment
        // post
        // user
        NodeHelper::createPath($session, '/functional/user');
        NodeHelper::createPath($session, '/functional/post');

        $user = new BlogUser();
        $user->id = '/functional/user/dtl';
        $user->name = 'daniel';
        $user->username = 'dtl';
        $user->status = 'query_builder';
        $this->dm->persist($user);

        $user = new BlogUser();
        $user->id = '/functional/user/js';
        $user->username = 'js';
        $user->name = 'johnsmith';
        $user->status = 'query_builder';
        $this->dm->persist($user);

        $user = new BlogUser();
        $user->id = '/functional/user/js2';
        $user->name = 'johnsmith';
        $user->username = 'js2';
        $user->status = 'another_johnsmith';
        $this->dm->persist($user);

        $post = new Post();
        $post->id = '/functional/post/post_1';
        $post->title = 'Post 2';
        $post->username = 'dtl';
        $this->dm->persist($post);

        $comment1 = new Comment();
        $comment1->id = '/functional/post/post_1/comment_1';
        $comment1->title = 'Comment 1';
        $this->dm->persist($comment1);

        $comment2 = new Comment();
        $comment2->id = '/functional/post/post_1/comment_2';
        $comment2->title = 'Comment 1';
        $this->dm->persist($comment2);

        $reply1 = new Comment();
        $reply1->id = '/functional/post/post_1/comment_1/reply_1';
        $reply1->title = 'Reply to Comment 1';
        $this->dm->persist($reply1);

        $post = new Post();
        $post->id = '/functional/post/post_2';
        $post->title = 'Post 2';
        $post->username = 'dtl';
        $this->dm->persist($post);

        $comment3 = new Comment();
        $comment3->id = '/functional/post/post_2/comment_3';
        $comment3->title = 'Comment 3';
        $this->dm->persist($comment3);

        $post3 = new Post();
        $post3->id = '/functional/post/post_3';
        $post3->title = 'Post 3';
        $post3->username = 'js';
        $this->dm->persist($post3);

        $this->dm->flush();
    }

    protected function createQb(): QueryBuilder
    {
        return $this->dm->createQueryBuilder();
    }

    public function testFrom(): void
    {
        $qb = $this->createQb();
        $qb->from()->document(BlogUser::class, 'a');

        // add where to stop rouge documents that havn't been stored in /functional/ from appearing.
        $qb->where()->eq()->field('a.status')->literal('query_builder')->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(2, $res);
    }

    public function testFromWithAlias(): void
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
    public function testComparison(): void
    {
        $qb = $this->createQb();
        $qb->from('a')->document(BlogUser::class, 'a');
        $qb->where()
            ->eq()
                ->field('a.username')
                ->literal('Not Exist')
            ->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(0, $res);

        $qb = $this->createQb();
        $qb->from('a')->document(BlogUser::class, 'a');
        $qb->where()
            ->eq()
                ->field('a.username')
                ->literal('dtl')
            ->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(1, $res);
    }

    public function testComposite(): void
    {
        $qb = $this->createQb();
        $qb->from('a')->document(BlogUser::class, 'a');
        $qb->where()
            ->orX()
                ->eq()->field('a.username')->literal('dtl')->end()
                ->eq()->field('a.username')->literal('js')->end()
            ->end();

        $res = $qb->getQuery()->execute();

        switch ($qb->getQuery()->getLanguage()) {
            case 'JCR-SQL2':
                $query = "SELECT * FROM [nt:unstructured] AS a WHERE ((a.[username] = 'dtl' OR a.[username] = 'js') AND (a.[phpcr:class] = 'Doctrine\Tests\Models\Blog\User' OR a.[phpcr:classparents] = 'Doctrine\Tests\Models\Blog\User'))";

                break;
            case 'sql':
                $query = "SELECT s FROM nt:unstructured AS a WHERE (a.[username] = 'dtl' OR a.[username] = 'js')";

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
                $query = "SELECT * FROM [nt:unstructured] AS a WHERE (((a.[username] = 'dtl' OR a.[username] = 'js') AND a.[name] = 'foobar') AND (a.[phpcr:class] = 'Doctrine\Tests\Models\Blog\User' OR a.[phpcr:classparents] = 'Doctrine\Tests\Models\Blog\User'))";

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
                $query = "SELECT * FROM [nt:unstructured] AS a WHERE ((((a.[username] = 'dtl' OR a.[username] = 'js') AND a.[name] = 'foobar') OR a.[name] = 'johnsmith') AND (a.[phpcr:class] = 'Doctrine\Tests\Models\Blog\User' OR a.[phpcr:classparents] = 'Doctrine\Tests\Models\Blog\User'))";

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
    public function testOrderBy(): void
    {
        $qb = $this->createQb();
        $qb->from('a')->document(BlogUser::class, 'a');
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
     */
    public function testOrderByNonSimpleField(): void
    {
        $qb = $this->createQb();
        $qb->from('a')->document(BlogUser::class, 'a');
        $qb->orderBy()->asc()->localname('a.username')->end();

        $this->expectException(InvalidArgumentException::class);
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

    public function testSelect(): void
    {
        // select one property
        $qb = $this->createQb();
        $qb->from('a')->document(BlogUser::class, 'a');
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
                $this->assertEquals(['a'], $result->getSelectorNames());
                $this->assertEquals(['a.username' => 'dtl'], $values);

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
                $this->assertEquals(['a'], $result->getSelectorNames());
                $this->assertEquals([
                    'a.username' => 'dtl',
                    'a.name' => 'daniel',
                ], $values);

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
                $this->assertEquals([
                    'a.status' => 'query_builder',
                ], $values);

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
    public function getTextSearches(): array
    {
        return [
            ['name', 'johnsmith', 2],
            ['username', 'dtl', 1],
        ];
    }

    /**
     * @dataProvider getTextSearches
     */
    public function testTextSearch($field, $search, $resCount): void
    {
        $qb = $this->createQb();
        $qb->from('a')->document(BlogUser::class, 'a');
        $qb->where()->fullTextSearch('a.'.$field, $search);
        $q = $qb->getQuery();

        $res = $q->execute();

        $this->assertCount($resCount, $res);
    }

    /**
     * @depends testFrom
     */
    public function testDescendant(): void
    {
        $qb = $this->createQb();
        $qb->from('a')->document(BlogUser::class, 'a');
        $qb->where()->descendant('/functional', 'a')->end();
        $q = $qb->getQuery();
        $res = $q->execute();
        $this->assertCount(3, $res);
    }

    /**
     * @depends testFrom
     */
    public function testSameNode(): void
    {
        $qb = $this->createQb();
        $qb->from('a')->document(BlogUser::class, 'a');
        $qb->where()->same('/functional/user/dtl', 'a');
        $q = $qb->getQuery();
        $res = $q->execute();

        $this->assertCount(1, $res);
    }

    public function testConditionWithNonExistingAlias(): void
    {
        $qb = $this->createQb();
        $qb->from('a')->document(BlogUser::class, 'b');
        $qb->where()->descendant('/functional', 'a')->end();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Alias name "a" is not known. The following aliases are valid: "b"');
        $qb->getQuery();
    }

    public function testConditionWithStaticLiteralOfDifferentType(): void
    {
        $user = new BlogUser();
        $user->id = '/functional/user/old';
        $user->name = 'I am old';
        $user->username = 'old';
        $user->status = 'query_builder';
        $user->age = 99;
        $this->dm->persist($user);
        $this->dm->flush();

        $qb = $this->createQb();
        $qb->from('a')->document(BlogUser::class, 'a');
        $qb->where()->eq()
            ->field('a.age')
            ->literal('99') // we pass the age here as a string type
;
        $q = $qb->getQuery();
        $res = $q->execute();

        $this->assertCount(1, $res);
    }
}
