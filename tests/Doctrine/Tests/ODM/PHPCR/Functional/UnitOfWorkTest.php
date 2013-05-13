<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\UnitOfWork;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsAddress;

use Doctrine\Tests\Models\Translation\Comment;

/**
 * @group functional
 */
class UnitOfWorkTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var UnitOfWork
     */
    private $uow;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->uow = $this->dm->getUnitOfWork();
        $this->resetFunctionalNode($this->dm);
    }

    public function testSchedules()
    {
        $user1 = new CmsUser();
        $user1->username = 'dantleech';
        $address = new CmsAddress();
        $address->city = "Springfield";
        $address->zip = "12354";
        $address->country = "Germany";
        $user1->address = $address;

        // getScheduledInserts
        $this->uow->scheduleInsert($user1);
        $this->uow->computeChangeSets();
        $scheduledInserts = $this->uow->getScheduledInserts();

        $this->assertCount(2, $scheduledInserts);
        $this->assertEquals($user1, current($scheduledInserts));
        $this->assertEquals(32, strlen(key($scheduledInserts)), 'Size of key is 32 chars (oid)');

        $user1->username = 'leechtdan';

        // getScheduledUpdates
        $this->uow->commit();
        $this->uow->scheduleInsert($user1);
        $this->uow->computeChangeSets();
        $scheduledUpdates = $this->uow->getScheduledUpdates();

        $this->assertCount(1, $scheduledUpdates);
        $this->assertEquals($user1, current($scheduledUpdates));
        $this->assertEquals(32, strlen(key($scheduledUpdates)), 'Size of key is 32 chars (oid)');

        // getScheduledRemovals
        $this->uow->scheduleRemove($user1);
        $scheduledRemovals = $this->uow->getScheduledRemovals();

        $this->assertCount(1, $scheduledRemovals);
        $this->assertEquals($user1, current($scheduledRemovals));
        $this->assertEquals(32, strlen(key($scheduledRemovals)), 'Size of key is 32 chars (oid)');

        // getScheduledMoves
        $this->uow->scheduleMove($user1, '/foobar');

        $scheduledMoves = $this->uow->getScheduledMoves();
        $this->assertCount(1, $scheduledMoves);
        $this->assertEquals(32, strlen(key($scheduledMoves)), 'Size of key is 32 chars (oid)');
        $this->assertEquals(array($user1, '/foobar'), current($scheduledMoves));
    }

    public function testGetScheduledReorders()
    {
        // TODO: do some real test
        $this->assertCount(0, $this->uow->getScheduledReorders());
    }

    public function testComputeChangeSetForTranslatableDocument()
    {
        $root = $this->dm->find(null, 'functional');
        $c1 = new Comment();
        $c1->name = 'c1';
        $c1->parent = $root;
        $c1->setText('deutsch');
        $this->dm->persist($c1);
        $this->dm->bindTranslation($c1, 'de');
        $c1->setText('english');
        $this->dm->bindTranslation($c1, 'en');
        $this->dm->flush();

        $c2 = new Comment();
        $c2->name = 'c2';
        $c2->parent = $root;
        $c2->setText('deutsch');
        $this->dm->persist($c2);
        $this->dm->bindTranslation($c2, 'de');
        $c2->setText('english');
        $this->dm->bindTranslation($c2, 'en');
        $this->uow->computeChangeSets();

        $this->assertCount(1, $this->uow->getScheduledInserts());
        $this->assertCount(0, $this->uow->getScheduledUpdates());
    }
}
