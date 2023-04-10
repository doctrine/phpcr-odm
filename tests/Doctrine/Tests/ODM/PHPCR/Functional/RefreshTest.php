<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\References\ParentTestObj;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;

class RefreshTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager([__DIR__]);
        $this->resetFunctionalNode($this->dm);
        $this->dm->getPhpcrSession()->save();
    }

    public function testBasicRefresh(): void
    {
        $user = new CmsUser();
        $user->id = '/functional/Guilherme';
        $user->username = 'gblanco';

        $this->dm->persist($user);
        $this->dm->flush();

        $user->username = 'mascot';

        $this->assertEquals('mascot', $user->username);
        $this->dm->refresh($user);
        $this->assertEquals('gblanco', $user->username);
    }

    public function testRefreshResetsCollection(): void
    {
        $user = new CmsUser();
        $user->id = '/functional/Guilherme';
        $user->username = 'gblanco';

        // Add a group
        $group1 = new CmsGroup();
        $group1->name = '12345';
        $group1->id = '/functional/group1';
        $user->addGroup($group1);

        // Add a group
        $group2 = new CmsGroup();
        $group2->name = '54321';
        $group2->id = '/functional/group2';

        $this->dm->persist($user);
        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();

        $user->addGroup($group2);

        $this->assertCount(2, $user->groups);
        $this->dm->refresh($user);

        $this->assertCount(1, $user->groups);
    }

    public function testRefreshProxy(): void
    {
        $parent = new ParentTestObj();
        $parent->id = '/functional/parent';
        $parent->name = 'parent';
        $child = new ParentTestObj();
        $child->id = '/functional/parent/child';
        $child->name = 'child';

        $this->dm->persist($parent);
        $this->dm->persist($child);
        $this->dm->flush();
        $this->dm->clear();

        $child = $this->dm->find(null, '/functional/parent/child');
        $this->assertInstanceOf(Proxy::class, $child->parent);
        $this->assertInstanceOf(ParentTestObj::class, $child->parent);
        $child->parent->name = 'x';

        $this->dm->refresh($child->parent);
        $this->assertEquals('parent', $child->parent->name);
    }

    public function testRefreshDetached(): void
    {
        $user = new CmsUser();
        $user->id = '/functional/Guilherme';
        $user->username = 'gblanco';

        $this->expectException(InvalidArgumentException::class);
        $this->dm->refresh($user);
    }

    public function testRefreshCollection(): void
    {
        $user = new CmsUser();
        $user->id = '/functional/Guilherme';
        $user->username = 'gblanco';

        // Add a group
        $group1 = new CmsGroup();
        $group1->name = '12345';
        $group1->id = '/functional/group1';
        $user->addGroup($group1);

        // Add a group
        $group2 = new CmsGroup();
        $group2->name = '54321';
        $group2->id = '/functional/group2';

        $this->dm->persist($user);
        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();

        $user->addGroup($group2);

        $this->assertCount(2, $user->groups);

        $user->groups->refresh();

        $this->assertCount(1, $user->groups);
    }
}
