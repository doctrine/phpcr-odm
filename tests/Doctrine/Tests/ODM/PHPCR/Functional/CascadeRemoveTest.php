<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;

class CascadeRemoveTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);

        $class = $this->dm->getClassMetadata(CmsUser::class);
        $class->mappings['groups']['cascade'] = ClassMetadata::CASCADE_REMOVE;

        $class = $this->dm->getClassMetadata(CmsUser::class);
        $class->mappings['articlesReferrers']['cascade'] = ClassMetadata::CASCADE_REMOVE;

        $class = $this->dm->getClassMetadata(CmsGroup::class);
        $class->mappings['users']['cascade'] = ClassMetadata::CASCADE_REMOVE;

        $class = $this->dm->getClassMetadata(CmsArticle::class);
        $class->mappings['user']['cascade'] = ClassMetadata::CASCADE_REMOVE;
    }

    public function testCascadeRemoveBidirectionalFromOwningSide()
    {
        $this->wrapRemove(function ($dm, $user, $group1, $group2) {
            $dm->remove($user);
            $dm->flush();
        });
    }

    public function testCascadeRemoveFromInverseSide()
    {
        $this->wrapRemove(function ($dm, $user, $group1, $group2) {
            $dm->remove($group1);
            $dm->flush();
        });
    }

    public function wrapRemove($closure)
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
        $user = new CmsUser();
        $user->username = 'beberlei';
        $user->name = 'Benjamin';

        $article = new CmsArticle();
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
        $user = new CmsUser();
        $user->username = 'beberlei';
        $user->name = 'Benjamin';

        $this->dm->persist($user);

        $this->dm->flush();
        $this->dm->flush();
    }
}
