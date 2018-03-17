<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\PHPCRException;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsArticlePerson;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;

class CascadePersistTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager([__DIR__]);
        $this->node = $this->resetFunctionalNode($this->dm);

        $class = $this->dm->getClassMetadata(CmsUser::class);
        $class->mappings['groups']['cascade'] = ClassMetadata::CASCADE_PERSIST;

        $class = $this->dm->getClassMetadata(CmsGroup::class);
        $class->mappings['users']['cascade'] = ClassMetadata::CASCADE_PERSIST;

        $class = $this->dm->getClassMetadata(CmsArticle::class);
        $class->mappings['user']['cascade'] = ClassMetadata::CASCADE_PERSIST;
    }

    public function testCascadePersistCollection()
    {
        $group1 = new CmsGroup();
        $group1->name = 'Test!';
        $group1->id = '/functional/group1';

        $group2 = new CmsGroup();
        $group2->name = 'Test!';
        $group2->id = '/functional/group2';

        $user = new CmsUser();
        $user->username = 'beberlei';
        $user->name = 'Benjamin';
        $user->addGroup($group1);
        $user->addGroup($group2);

        $this->dm->persist($user);

        $this->assertTrue($this->dm->contains($group1));
        $this->assertTrue($this->dm->contains($group2));

        $this->dm->flush();
        $this->dm->clear();

        $pUser = $this->dm->find(CmsUser::class, $user->id);
        $this->assertCount(2, $pUser->groups);
    }

    public function testCascadePersistForManagedDocument()
    {
        $user = new CmsUser();
        $user->username = 'beberlei';
        $user->name = 'Benjamin';
        $this->dm->persist($user);
        $this->dm->flush();

        $group1 = new CmsGroup();
        $group1->name = 'Test!';
        $group1->id = '/functional/group1';

        $group2 = new CmsGroup();
        $group2->name = 'Test!';
        $group2->id = '/functional/group2';

        $user->addGroup($group1);
        $user->addGroup($group2);
        $this->dm->persist($user);

        $this->assertTrue($this->dm->contains($group1));
        $this->assertTrue($this->dm->contains($group2));

        $this->dm->flush();
        $this->dm->clear();

        $pUser = $this->dm->find(CmsUser::class, $user->id);
        $this->assertCount(2, $pUser->groups);
    }

    public function testCascadePersistSingleDocument()
    {
        $user = new CmsUser();
        $user->username = 'beberlei';
        $user->name = 'Benjamin';

        $article = new CmsArticle();
        $article->text = 'foo';
        $article->topic = 'bar';
        $article->user = $user;
        $article->id = '/functional/article';

        $this->dm->persist($article);

        $this->assertTrue($this->dm->contains($user));

        $this->dm->flush();
        $this->dm->clear();

        $article = $this->dm->find(CmsArticle::class, $article->id);
        $this->assertEquals($user->id, $article->user->getId());
    }

    public function testCascadeManagedDocumentCollectionDuringFlush()
    {
        $user = new CmsUser();
        $user->username = 'beberlei';
        $user->name = 'Benjamin';
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $group1 = new CmsGroup();
        $group1->name = 'Test!';
        $group1->id = '/functional/group1';

        $group2 = new CmsGroup();
        $group2->name = 'Test!';
        $group2->id = '/functional/group2';

        $user = $this->dm->find(CmsUser::class, $user->id);
        $user->addGroup($group1);
        $user->addGroup($group2);

        $this->assertFalse($this->dm->contains($group1));
        $this->assertFalse($this->dm->contains($group2));

        $this->dm->flush();
        $this->dm->clear();

        $pUser = $this->dm->find(CmsUser::class, $user->id);
        $this->assertCount(2, $pUser->groups);
    }

    public function testCascadeManagedDocumentReferenceDuringFlush()
    {
        $article = new CmsArticle();
        $article->text = 'foo';
        $article->topic = 'bar';
        $article->id = '/functional/article';

        $this->dm->persist($article);

        $user = new CmsUser();
        $user->username = 'beberlei';
        $user->name = 'Benjamin';
        $article->user = $user;

        $this->assertFalse($this->dm->contains($user));

        $this->dm->flush();
        $this->dm->clear();

        $article = $this->dm->find(CmsArticle::class, $article->id);
        $this->assertEquals($user->id, $article->user->getId());
    }

    public function testCascadeManagedDocumentReferrerDuringFlush()
    {
        $user = new CmsUser();
        $user->username = 'dbu';
        $user->name = 'David';
        $this->dm->persist($user);

        $article = new CmsArticle();
        $article->text = 'foo';
        $article->topic = 'bar';
        $article->id = '/functional/article_referrer';
        $user->articlesReferrers->add($article);

        $this->assertFalse($this->dm->contains($article));

        $this->dm->flush();

        $this->assertEquals($user, $article->user);

        $this->dm->clear();

        $user = $this->dm->find(CmsUser::class, '/functional/dbu');
        $this->assertNotNull($user);
        $this->assertGreaterThanOrEqual(1, count($user->articlesReferrers));
        $savedArticle = $user->articlesReferrers->first();
        $this->assertInstanceOf(CmsArticle::class, $savedArticle);
        $this->assertEquals($article->id, $savedArticle->id);

        $savedArticle->user = null;
        $this->dm->flush();
        $this->dm->clear();

        $removedArticle = $user->articlesReferrers->first();
        $this->assertNull($removedArticle->user);
    }

    /**
     * Almost the same as testCascadeManagedDocumentReferrerDuringFlush but
     * using a plain array instead of an ArrayCollection to be sure sloppy
     * initialization by a user does not lead to issues.
     */
    public function testCascadeManagedDocumentReferrerDuringFlushArray()
    {
        $user = new CmsUser();
        $user->username = 'dbu';
        $user->name = 'David';
        $this->dm->persist($user);

        $article = new CmsArticle();
        $article->text = 'foo';
        $article->topic = 'bar';
        $article->id = '/functional/article_referrer';
        $user->articlesReferrers = [$article];

        $this->dm->flush();

        $this->assertEquals($user, $article->user);

        $this->dm->clear();

        $user = $this->dm->find(CmsUser::class, '/functional/dbu');
        $this->assertNotNull($user);
        $this->assertGreaterThanOrEqual(1, count($user->articlesReferrers));
        $savedArticle = $user->articlesReferrers->first();
        $this->assertInstanceOf(CmsArticle::class, $savedArticle);
        $this->assertEquals($article->id, $savedArticle->id);
    }

    public function testCascadeReferenceArray()
    {
        $article = new CmsArticle();
        $article->text = 'foo';
        $article->topic = 'bar';
        $article->id = '/functional/article';
        $article->user = [];

        $this->expectException(PHPCRException::class);
        $this->expectExceptionMessage('Referenced document is not stored correctly in a reference-one property. Do not use array notation');
        $this->dm->persist($article);
    }

    public function testCascadeReferenceNoObject()
    {
        $article = new CmsArticle();
        $article->text = 'foo';
        $article->topic = 'bar';
        $article->id = '/functional/article';
        $article->user = 'This is not an object';

        $this->expectException(PHPCRException::class);
        $this->expectExceptionMessage('A reference field may only contain mapped documents, found <string> in field "user" of "Doctrine\Tests\Models\CMS\CmsArticle');
        $this->dm->persist($article);
    }

    public function testCascadeReferenceManyNoArray()
    {
        $user = new CmsUser();
        $user->name = 'foo';
        $user->username = 'bar';
        $user->id = '/functional/user';
        $user->groups = $this;

        $this->expectException(PHPCRException::class);
        $this->expectExceptionMessage('Referenced documents are not stored correctly in a reference-many property. Use array notation or a (ReferenceMany)Collection');
        $this->dm->persist($user);
    }

    public function testCascadeReferenceManyNoObject()
    {
        $user = new CmsUser();
        $user->name = 'foo';
        $user->username = 'bar';
        $user->id = '/functional/user';
        $user->groups = ['this is a bad idea'];

        $this->expectException(PHPCRException::class);
        $this->expectExceptionMessage('A reference field may only contain mapped documents, found <string> in field "groups" of "Doctrine\Tests\Models\CMS\CmsUser');
        $this->dm->persist($user);
    }

    /**
     * Test Referrers ManyToMany cascade Flush.
     */
    public function testCascadeManagedDocumentReferrerMtoMDuringFlush()
    {
        $article1 = new CmsArticle();
        $article1->text = 'foo';
        $article1->topic = 'bar';
        $article1->id = '/functional/article_m2m_referrer_1';
        $this->dm->persist($article1);

        $article2 = new CmsArticle();
        $article2->text = 'foo2';
        $article2->topic = 'bar2';
        $article2->id = '/functional/article_m2m_referrer_2';
        $this->dm->persist($article2);

        $superman = new CmsArticlePerson();
        $superman->name = 'superman';

        $this->dm->persist($superman);

        $article1->addPerson($superman);

        $this->dm->flush();

        $this->dm->refresh($superman);

        $this->assertEquals($superman, $article1->getPersons()->first());

        // we want to attach article2 to superman
        // in the form of edition, we will submit article1 and article2 at the same time
        $superman->getArticlesReferrers()->add($article1);
        $superman->getArticlesReferrers()->add($article2);
        $this->dm->flush();
        $this->dm->refresh($superman);

        $this->assertEquals(1, $article1->getPersons()->count());
        $this->assertEquals(2, $superman->getArticlesReferrers()->count());

        $this->dm->clear();
    }
}
