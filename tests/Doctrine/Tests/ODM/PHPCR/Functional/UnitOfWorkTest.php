<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Exception\OutOfBoundsException;
use Doctrine\ODM\PHPCR\UnitOfWork;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\References\ParentNoNodeNameTestObj;
use Doctrine\Tests\Models\References\ParentTestObj;
use Doctrine\Tests\Models\Translation\Comment;
use Doctrine\Tests\Models\CMS\CmsArticleFolder;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsBlogPost;
use Doctrine\Tests\Models\CMS\CmsBlogInvalidChild;
use Doctrine\Tests\Models\CMS\CmsBlogFolder;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;

/**
 * @group functional
 */
class UnitOfWorkTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var UnitOfWork
     */
    private $uow;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager();
        $this->uow = $this->dm->getUnitOfWork();
        $this->resetFunctionalNode($this->dm);
    }

    public function testSchedules()
    {
        $user1 = new CmsUser();
        $user1->username = 'dantleech';
        $address = new CmsAddress();
        $address->city = "Springfield";
        $address->zip = "12354";
        $address->country = "Germany";
        $user1->address = $address;

        // getScheduledInserts
        $this->uow->scheduleInsert($user1);
        $this->uow->computeChangeSets();
        $scheduledInserts = $this->uow->getScheduledInserts();

        $this->assertCount(2, $scheduledInserts);
        $this->assertEquals($user1, current($scheduledInserts));
        $this->assertEquals(32, strlen(key($scheduledInserts)), 'Size of key is 32 chars (oid)');

        $user1->username = 'leechtdan';

        // getScheduledUpdates
        $this->uow->commit();
        $this->uow->scheduleInsert($user1);
        $this->uow->computeChangeSets();
        $scheduledUpdates = $this->uow->getScheduledUpdates();

        $this->assertCount(1, $scheduledUpdates);
        $this->assertEquals($user1, current($scheduledUpdates));
        $this->assertEquals(32, strlen(key($scheduledUpdates)), 'Size of key is 32 chars (oid)');

        // getScheduledRemovals
        $this->uow->scheduleRemove($user1);
        $scheduledRemovals = $this->uow->getScheduledRemovals();

        $this->assertCount(1, $scheduledRemovals);
        $this->assertEquals($user1, current($scheduledRemovals));
        $this->assertEquals(32, strlen(key($scheduledRemovals)), 'Size of key is 32 chars (oid)');

        // getScheduledMoves
        $this->uow->scheduleMove($user1, '/foobar');

        $scheduledMoves = $this->uow->getScheduledMoves();
        $this->assertCount(1, $scheduledMoves);
        $this->assertEquals(32, strlen(key($scheduledMoves)), 'Size of key is 32 chars (oid)');
        $this->assertEquals(array($user1, '/foobar'), current($scheduledMoves));
    }

    public function testMoveParentNoNodeName()
    {
        $root = $this->dm->find(null, 'functional');

        $parent1 = new ParentTestObj();
        $parent1->nodename = "root1";
        $parent1->name = "root1";
        $parent1->setParentDocument($root);

        $parent2 = new ParentTestObj();
        $parent2->name = "/root2";
        $parent2->nodename = "root2";
        $parent2->setParentDocument($root);

        $child = new ParentNoNodeNameTestObj();
        $child->setParentDocument($parent1);
        $child->name = "child";

        $this->dm->persist($parent1);
        $this->dm->persist($parent2);
        $this->dm->persist($child);

        $this->dm->flush();

        $child->setParentDocument($parent2);

        $this->dm->persist($child);

        try {
            $this->dm->flush();
        } catch (\Exception $e) {
            $this->fail('An exception has been raised moving a child node from parent1 to parent2.');
        }
    }

    public function testGetScheduledReorders()
    {
        // TODO: do some real test
        $this->assertCount(0, $this->uow->getScheduledReorders());
    }

    public function testComputeChangeSetForTranslatableDocument()
    {
        $root = $this->dm->find(null, 'functional');
        $c1 = new Comment();
        $c1->name = 'c1';
        $c1->parent = $root;
        $c1->setText('deutsch');
        $this->dm->persist($c1);
        $this->dm->bindTranslation($c1, 'de');
        $c1->setText('english');
        $this->dm->bindTranslation($c1, 'en');
        $this->dm->flush();

        $c2 = new Comment();
        $c2->name = 'c2';
        $c2->parent = $root;
        $c2->setText('deutsch');
        $this->dm->persist($c2);
        $this->dm->bindTranslation($c2, 'de');
        $c2->setText('english');
        $this->dm->bindTranslation($c2, 'en');
        $this->uow->computeChangeSets();

        $this->assertCount(1, $this->uow->getScheduledInserts());
        $this->assertCount(0, $this->uow->getScheduledUpdates());
    }

    public function testFetchingMultipleHierarchicalObjectsWithChildIdFirst()
    {
        $parent           = new ParentTestObj();
        $parent->nodename = 'parent';
        $parent->name     = 'parent';
        $parent->parent   = $this->dm->find(null, 'functional');

        $child            = new ParentTestObj();
        $child->nodename  = 'child';
        $child->name      = 'child';
        $child->parent    = $parent;

        $this->dm->persist($parent);
        $this->dm->persist($child);

        $parentId = $this->uow->getDocumentId($parent);
        $childId  = $this->uow->getDocumentId($child);

        $this->dm->flush();
        $this->dm->clear();

        // this forces the objects to be loaded in an order where the $parent will become a proxy
        $documents = $this->dm->findMany(ParentTestObj::class, array($childId, $parentId));

        $this->assertCount(2, $documents);

        /* @var $child ParentTestObj */
        /* @var $parent ParentTestObj */
        $child  = $documents->first();
        $parent = $documents->last();

        $this->assertSame($child->parent, $parent);
        $this->assertSame('parent', $parent->nodename);
    }

    public function testRequiredClassesInvalidChildren()
    {
        $articleFolder = new CmsArticleFolder();
        $articleFolder->id = '/functional/articles';

        $article = new CmsGroup();
        $article->id = '/functional/articles/address';
        $article->name = 'invalid-child';

        $this->dm->persist($articleFolder);
        $this->dm->persist($article);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Document "Doctrine\Tests\Models\CMS\CmsArticleFolder" does not allow children of type "Doctrine\Tests\Models\CMS\CmsGroup". Allowed child classes "Doctrine\Tests\Models\CMS\CmsArticle"');
        $this->dm->flush();
    }

    public function testRequiredClassesValidChildren()
    {
        $articleFolder = new CmsArticleFolder();
        $articleFolder->id = '/functional/articles';

        $article = new CmsArticle();
        $article->id = '/functional/articles/article';
        $article->topic = 'greetings';
        $article->text = 'Hello World';

        $this->dm->persist($articleFolder);
        $this->dm->persist($article);
        $this->dm->flush();
    }

    public function testRequiredClassesInvalidUpdate()
    {
        $articleFolder = new CmsArticleFolder();
        $articleFolder->id = '/functional/articles';

        $article = new CmsGroup();
        $article->id = '/functional/address';
        $article->name = 'invalid-child';

        $this->dm->persist($articleFolder);
        $this->dm->persist($article);
        $this->dm->flush();

        $this->dm->move($article, '/functional/articles/address');

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Document "Doctrine\Tests\Models\CMS\CmsArticleFolder" does not allow children of type "Doctrine\Tests\Models\CMS\CmsGroup". Allowed child classes "Doctrine\Tests\Models\CMS\CmsArticle"');
        $this->dm->flush();
    }

    public function testRequiredClassesAddToChildrenValid()
    {
        $post = new CmsBlogPost();
        $post->name = 'hello';

        $postFolder = new CmsBlogFolder();
        $postFolder->id = '/functional/posts';
        $postFolder->posts = new ArrayCollection(array(
            $post
        ));

        $this->dm->persist($postFolder);
        $this->dm->flush();
    }

    public function testRequiredClassesAddToChildrenInvalid()
    {
        $post = new CmsBlogInvalidChild();
        $post->name = 'hello';

        $postFolder = new CmsBlogFolder();
        $postFolder->id = '/functional/posts';
        $postFolder->posts = new ArrayCollection(array(
            $post
        ));

        $this->dm->persist($postFolder);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Document "Doctrine\Tests\Models\CMS\CmsBlogFolder" does not allow children of type "Doctrine\Tests\Models\CMS\CmsBlogInvalidChild". Allowed child classes "Doctrine\Tests\Models\CMS\CmsBlogPost"');
        $this->dm->flush();
    }

    public function testRequiredClassesAddToChildrenInvalidOnUpdate()
    {
        $post = new CmsBlogPost();
        $post->name = 'hello';

        $postFolder = new CmsBlogFolder();
        $postFolder->id = '/functional/posts';
        $postFolder->posts = new ArrayCollection(array(
            $post
        ));

        $this->dm->persist($postFolder);
        $this->dm->flush();
        $this->dm->clear();

        $postFolder = $this->dm->find(null, '/functional/posts');

        $post = new CmsBlogInvalidChild();
        $post->name = 'wolrd';
        $postFolder->posts->add($post);

        $this->dm->persist($postFolder);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Document "Doctrine\Tests\Models\CMS\CmsBlogFolder" does not allow children of type "Doctrine\Tests\Models\CMS\CmsBlogInvalidChild". Allowed child classes "Doctrine\Tests\Models\CMS\CmsBlogPost"');
        $this->dm->flush();
    }
}
