<?php

namespace Doctrine\ODM\PHPCR\Functional;

/**
 * @group functional
 */
class OneToManyAssocationTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    private $articleIds = array();
    private $userIds = array();
    private $dm;

    public function setUp()
    {
        $user1 = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user1->username = "beberlei";
        $user1->status = "active";
        $user1->name = "Benjamin";

        $user2 = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user2->username = "lsmith";
        $user2->status = "active";
        $user2->name = "Lukas";

        $article1 = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article1->text = "Foo";
        $article1->topic = "Foo";
        $article1->setAuthor($user1);

        $article2 = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article2->text = "Foo";
        $article2->topic = "Foo";
        $article2->setAuthor($user1);

        $this->dm = $this->createDocumentManager();
        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($article1);
        $this->dm->persist($article2);
        $this->dm->flush();

        $this->dm->clear();

        $this->articleIds = array($article1->id, $article2->id);
        $this->userIds = array($user1->id, $user2->id);
    }

    public function testTraverseInverseSide()
    {
        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userIds[0]);

        $this->assertType('Doctrine\ODM\PHPCR\PersistentCollection', $user->articles);
        $this->assertFalse($user->articles->isInitialized);
        $this->assertEquals(2, count($user->articles));

        $this->assertContains($user->articles[0]->id, $this->articleIds);
        $this->assertContains($user->articles[1]->id, $this->articleIds);
    }

    public function testInverseSideChangesAreIgnored()
    {
        $user1 = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userIds[0]);
        $article3 = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article3->text = "Foo";
        $article3->topic = "Foo";
        $user1->articles[] = $article3;

        $this->dm->persist($article3);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userIds[0]);
        $this->assertEquals(2, count($user->articles));
    }
}