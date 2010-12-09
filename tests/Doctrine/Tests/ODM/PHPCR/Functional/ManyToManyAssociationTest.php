<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

/**
 * @group functional
 */
class ManyToManyAssociationTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    private $userId;
    private $groupIds = array();
    private $dm;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();

        $user1 = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user1->username = "beberlei";
        $user1->status = "active";
        $user1->name = "Benjamin";

        $user2 = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user2->username = "lsmith";
        $user2->status = "active";
        $user2->name = "Lukas";

        $group1 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group1->name = "Admin";

        $group2 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group2->name = "User";

        $user1->addGroup($group1);
        $user1->addGroup($group2);

        $user2->addGroup($group2);

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->persist($group1);
        $this->dm->persist($group2);
        $this->dm->flush();
        $this->dm->clear();

        $this->userId = $user1->id;
        $this->groupIds = array($group1->id, $group2->id);
    }

    public function testSaveManyToMany()
    {
        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $this->assertType('Doctrine\ODM\PHPCR\PersistentCollection', $user->groups);
        $this->assertFalse($user->groups->isInitialized);
        $this->assertEquals(2, count($user->groups));
        $this->assertTrue($user->groups->isInitialized);

        $group3 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group3->name = "User";
        $user->addGroup($group3);
        $this->dm->persist($group3);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        $this->assertEquals(3, count($user->groups));
    }

    public function testInverseManyToManyLazyLoad()
    {
        $group = $this->dm->find('Doctrine\Tests\Models\CMS\CmsGroup', $this->groupIds[0]);
        $this->assertType('Doctrine\Tests\Models\CMS\CmsGroup', $group);

        $this->assertType('Doctrine\ODM\PHPCR\PersistentCollection', $group->users);
        $this->assertFalse($group->users->isInitialized);
        $this->assertEquals(1, count($group->users));
        $this->assertTrue($group->users->isInitialized);

        $this->assertEquals('beberlei', $group->users[0]->getUsername());
        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $this->assertSame($user, $group->users[0]);
    }

    public function testInverseManyToManyIdentityMap()
    {
        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $group = $this->dm->find('Doctrine\Tests\Models\CMS\CmsGroup', $this->groupIds[0]);
        $this->assertType('Doctrine\Tests\Models\CMS\CmsGroup', $group);

        $this->assertType('Doctrine\ODM\PHPCR\PersistentCollection', $group->users);
        $this->assertFalse($group->users->isInitialized);
        $this->assertEquals(1, count($group->users));
        $this->assertTrue($group->users->isInitialized);

        $this->assertSame($user, $group->users[0]);
    }

    public function testInverseManyToManySeveralEntries()
    {
        $group = $this->dm->find('Doctrine\Tests\Models\CMS\CmsGroup', $this->groupIds[1]);

        $this->assertEquals(2, count($group->users));
        $this->assertTrue($group->users->isInitialized);
    }

    public function testUpdateInverseSideIsIgnored()
    {
        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);

        $group3 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group3->name = "User";
        $group3->users[] = $user;

        $this->dm->persist($group3);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $this->assertEquals(2, count($user->groups));
    }

    public function testFlushingOwningSideWithAssocationChangesTwiceOnlySavesOnce()
    {
        $listener = new CountScheduledUpdatesListener();
        $this->dm->getEventManager()->addEventListener(array('preUpdate'), $listener);
        $this->dm->clear(); // new unit of work has new event listener

        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $group3 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group3->name = "User";

        $user->addGroup($group3);
        $this->dm->persist($group3);

        $this->dm->flush();
        $this->assertEquals(2, $listener->preUpdates);
        
        $this->dm->flush();
        $this->assertEquals(2, $listener->preUpdates);
    }
}

class CountScheduledUpdatesListener
{
    public $preUpdates = 0;

    public function preUpdate($args)
    {
        $uow = $args->getDocumentManager()->getUnitOfWork();
        $this->preUpdates++;
    }
}