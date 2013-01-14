<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class CascadeRefreshTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
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
        $class->mappings['groups']['cascade'] = ClassMetadata::CASCADE_REFRESH;

        $class = $this->dm->getClassMetadata('Doctrine\Tests\Models\CMS\CmsGroup');
        $class->mappings['users']['cascade'] = ClassMetadata::CASCADE_REFRESH;

        $class = $this->dm->getClassMetadata('Doctrine\Tests\Models\CMS\CmsArticle');
        $class->mappings['user']['cascade'] = ClassMetadata::CASCADE_REFRESH;
    }

    public function testCascadeRefresh()
    {
        $group1 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group1->name = "Test!";
        $group1->id = '/functional/group1';

        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";
        $user->addGroup($group1);

        $this->dm->persist($user);
        $this->dm->persist($group1);

        $this->dm->flush();

        $this->assertEquals(1, count($user->groups));

        $group1->name = "Test2";
        $user->username = "beberlei2";

        $this->dm->refresh($user);

        $this->assertEquals("beberlei", $user->username);
        $this->assertEquals("Test!", $group1->name);
    }
}