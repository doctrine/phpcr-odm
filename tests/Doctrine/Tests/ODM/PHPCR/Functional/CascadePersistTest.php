<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class CascadePersistTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);

        $class = $this->dm->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $class->mappings['groups']['cascade'] = ClassMetadata::CASCADE_PERSIST;

        $class = $this->dm->getClassMetadata('Doctrine\Tests\Models\CMS\CmsGroup');
        $class->mappings['users']['cascade'] = ClassMetadata::CASCADE_PERSIST;

        $class = $this->dm->getClassMetadata('Doctrine\Tests\Models\CMS\CmsArticle');
        $class->mappings['user']['cascade'] = ClassMetadata::CASCADE_PERSIST;
    }

    public function testCascadePersistCollection()
    {
        $group1 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group1->name = "Test!";
        $group1->id = '/functional/group1';

        $group2 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group2->name = "Test!";
        $group2->id = '/functional/group2';

        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";
        $user->addGroup($group1);
        $user->addGroup($group2);

        $this->dm->persist($user);

        $this->assertTrue($this->dm->contains($group1));
        $this->assertTrue($this->dm->contains($group2));

        $this->dm->flush();
        $this->dm->clear();

        $pUser = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        $this->assertEquals(2, count($pUser->groups));
    }

    public function testCascadePersistForManagedDocument()
    {
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";
        $this->dm->persist($user);
        $this->dm->flush();

        $group1 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group1->name = "Test!";
        $group1->id = '/functional/group1';

        $group2 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group2->name = "Test!";
        $group2->id = '/functional/group2';

        $user->addGroup($group1);
        $user->addGroup($group2);
        $this->dm->persist($user);

        $this->assertTrue($this->dm->contains($group1));
        $this->assertTrue($this->dm->contains($group2));

        $this->dm->flush();
        $this->dm->clear();

        $pUser = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        $this->assertEquals(2, count($pUser->groups));
    }

    public function testCascadePersistSingleDocument()
    {
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";

        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->text = "foo";
        $article->topic = "bar";
        $article->user = $user;
        $article->id = '/functional/article';

        $this->dm->persist($article);

        $this->assertTrue($this->dm->contains($user));

        $this->dm->flush();
        $this->dm->clear();

        $article = $this->dm->find('Doctrine\Tests\Models\CMS\CmsArticle', $article->id);
        $this->assertEquals($user->id, $article->user->getId());
    }

    public function testCascadeManagedDocumentCollectionDuringFlush()
    {
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $group1 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group1->name = "Test!";
        $group1->id = '/functional/group1';

        $group2 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group2->name = "Test!";
        $group2->id = '/functional/group2';

        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        $user->addGroup($group1);
        $user->addGroup($group2);

        $this->assertFalse($this->dm->contains($group1));
        $this->assertFalse($this->dm->contains($group2));

        $this->dm->flush();
        $this->dm->clear();

        $pUser = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        $this->assertEquals(2, count($pUser->groups));
    }

    public function testCascadeManagedDocumentReferenceDuringFlush()
    {
        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->text = "foo";
        $article->topic = "bar";
        $article->id = '/functional/article';

        $this->dm->persist($article);

        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";
        $article->user = $user;

        $this->assertFalse($this->dm->contains($user));

        $this->dm->flush();
        $this->dm->clear();

        $article = $this->dm->find('Doctrine\Tests\Models\CMS\CmsArticle', $article->id);
        $this->assertEquals($user->id, $article->user->getId());
    }

    public function testCascadeManagedDocumentReferrerDuringFlush()
    {
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = "dbu";
        $user->name = "David";
        $this->dm->persist($user);

        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->text = "foo";
        $article->topic = "bar";
        $article->id = '/functional/article_referrer';
        $user->articlesReferrers->add($article);

        $this->assertFalse($this->dm->contains($article));

        $this->dm->flush();

        $this->assertEquals($user, $article->user);

        $this->dm->clear();

        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', '/functional/dbu');
        $this->assertNotNull($user);
        $this->assertTrue(1 <= count($user->articlesReferrers));
        $savedArticle = $user->articlesReferrers->first();
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsArticle', $savedArticle);
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
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = "dbu";
        $user->name = "David";
        $this->dm->persist($user);

        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->text = "foo";
        $article->topic = "bar";
        $article->id = '/functional/article_referrer';
        $user->articlesReferrers = array($article);

        $this->dm->flush();

        $this->assertEquals($user, $article->user);

        $this->dm->clear();

        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', '/functional/dbu');
        $this->assertNotNull($user);
        $this->assertTrue(1 <= count($user->articlesReferrers));
        $savedArticle = $user->articlesReferrers->first();
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsArticle', $savedArticle);
        $this->assertEquals($article->id, $savedArticle->id);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testCascadeReferenceArray()
    {
        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->text = "foo";
        $article->topic = "bar";
        $article->id = '/functional/article';
        $article->user = array();

        $this->dm->persist($article);
        $this->dm->flush();
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testCascadeReferenceNoObject()
    {
        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->text = "foo";
        $article->topic = "bar";
        $article->id = '/functional/article';
        $article->user = "This is not an object";

        $this->dm->persist($article);
        $this->dm->flush();
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testCascadeReferenceManyNoArray()
    {
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->name = "foo";
        $user->username = "bar";
        $user->id = '/functional/user';
        $user->groups = $this;

        $this->dm->persist($user);
        $this->dm->flush();
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testCascadeReferenceManyNoObject()
    {
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->name = "foo";
        $user->username = "bar";
        $user->id = '/functional/user';
        $user->groups = array("this is a bad idea");

        $this->dm->persist($user);
        $this->dm->flush();
    }

    /**
     * Test Referrers ManyToMany cascade Flush
     */
    public function testCascadeManagedDocumentReferrerMtoMDuringFlush()
    {
        $article1 = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article1->text = "foo";
        $article1->topic = "bar";
        $article1->id = '/functional/article_m2m_referrer_1';
        $this->dm->persist($article1);

        $article2 = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article2->text = "foo2";
        $article2->topic = "bar2";
        $article2->id = '/functional/article_m2m_referrer_2';
        $this->dm->persist($article2);

        $superman = new \Doctrine\Tests\Models\CMS\CmsArticlePerson();
        $superman->name = "superman";

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
