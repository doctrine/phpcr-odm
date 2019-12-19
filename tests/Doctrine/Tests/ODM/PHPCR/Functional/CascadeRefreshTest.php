<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;

class CascadeRefreshTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager([__DIR__]);
        $this->node = $this->resetFunctionalNode($this->dm);

        $class = $this->dm->getClassMetadata(CmsUser::class);
        $class->mappings['groups']['cascade'] = ClassMetadata::CASCADE_REFRESH;

        $class = $this->dm->getClassMetadata(CmsGroup::class);
        $class->mappings['users']['cascade'] = ClassMetadata::CASCADE_REFRESH;

        $class = $this->dm->getClassMetadata(CmsArticle::class);
        $class->mappings['user']['cascade'] = ClassMetadata::CASCADE_REFRESH;
    }

    public function testCascadeRefresh()
    {
        $group1 = new CmsGroup();
        $group1->name = 'Test!';
        $group1->id = '/functional/group1';

        $user = new CmsUser();
        $user->username = 'beberlei';
        $user->name = 'Benjamin';
        $user->addGroup($group1);

        $this->dm->persist($user);
        $this->dm->persist($group1);

        $this->dm->flush();

        $this->assertEquals(1, count($user->groups));

        $group1->name = 'Test2';
        $user->username = 'beberlei2';

        $this->dm->refresh($user);

        $this->assertEquals('beberlei', $user->username);
        $this->assertEquals('Test!', $group1->name);
    }
}
