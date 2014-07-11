<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class CascadeRemoveTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
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
        $class->mappings['groups']['cascade'] = ClassMetadata::CASCADE_REMOVE;

        $class = $this->dm->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $class->mappings['articlesReferrers']['cascade'] = ClassMetadata::CASCADE_REMOVE;

        $class = $this->dm->getClassMetadata('Doctrine\Tests\Models\CMS\CmsGroup');
        $class->mappings['users']['cascade'] = ClassMetadata::CASCADE_REMOVE;

        $class = $this->dm->getClassMetadata('Doctrine\Tests\Models\CMS\CmsArticle');
        $class->mappings['user']['cascade'] = ClassMetadata::CASCADE_REMOVE;
    }

    public function testCascadeRemoveBidirectionalFromOwningSide()
    {
        $this->wrapRemove(function($dm, $user, $group1, $group2) {
                $dm->remove($user);
                $dm->flush();
            });
    }

    public function testCascadeRemoveFromInverseSide()
    {
        $this->wrapRemove(function($dm, $user, $group1, $group2) {
                $dm->remove($group1);
                $dm->flush();
            });
    }

    public function wrapRemove($closure)
    {
        $group1 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group1->name = 'Test!';
        $group1->id = '/functional/group1';

        $group2 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group2->name = 'Test!';
        $group2->id = '/functional/group2';

        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = 'beberlei';
        $user->name = 'Benjamin';
        $user->addGroup($group1);
        $user->addGroup($group2);

        $this->dm->persist($user);
        $this->dm->persist($group1);
        $this->dm->persist($group2);

        $this->dm->flush();

        $this->assertTrue($this->dm->contains($user));
        $this->assertTrue($this->dm->contains($group1));
        $this->assertTrue($this->dm->contains($group2));

        $closure($this->dm, $user, $group1, $group2);

        $this->assertFalse($this->dm->contains($user));
        $this->assertFalse($this->dm->contains($group1));
        $this->assertFalse($this->dm->contains($group2));
    }

    public function testCascadeRemoveSingleDocument()
    {
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = 'beberlei';
        $user->name = 'Benjamin';

        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->text = 'foo';
        $article->topic = 'bar';
        $article->user = $user;
        $article->id = '/functional/article';

        $this->dm->persist($article);
        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->remove($article);
        $this->dm->flush();

        $this->assertFalse($this->dm->contains($user));
        $this->assertFalse($this->dm->contains($article));
    }

    public function testCascadeRemoveReferrer()
    {
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = 'beberlei';
        $user->name = 'Benjamin';

        $this->dm->persist($user);

        $this->dm->flush();
        $this->dm->flush();
    }
}
