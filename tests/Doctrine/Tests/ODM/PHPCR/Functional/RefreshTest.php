<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsGroup;

class RefreshTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    private $dm;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);
        $this->dm->getPhpcrSession()->save();
    }

    public function testBasicRefresh()
    {
        $user = new CmsUser;
        $user->id = '/functional/Guilherme';
        $user->username = 'gblanco';

        $this->dm->persist($user);
        $this->dm->flush();

        $user->username = 'mascot';

        $this->assertEquals('mascot', $user->username);
        $this->dm->refresh($user);
        $this->assertEquals('gblanco', $user->username);
    }

    public function testRefreshResetsCollection()
    {
        $user = new CmsUser;
        $user->id = '/functional/Guilherme';
        $user->username = 'gblanco';

        // Add a group
        $group1 = new CmsGroup;
        $group1->name = "12345";
        $group1->id = '/functional/group1';
        $user->addGroup($group1);

        // Add a group
        $group2 = new CmsGroup;
        $group2->name = "54321";
        $group2->id = '/functional/group2';

        $this->dm->persist($user);
        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();

        $user->addGroup($group2);

        $this->assertEquals(2, count($user->groups));
        $this->dm->refresh($user);

        $this->assertEquals(1, count($user->groups));
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testRefreshDetached()
    {
        $user = new CmsUser;
        $user->id = '/functional/Guilherme';
        $user->username = 'gblanco';
        $this->dm->refresh($user);
    }
}