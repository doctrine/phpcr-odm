<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsAddress;

/**
 * @group functional
 */
class UnitOfWorkTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    public function setUp()
    {
        $this->dm = $this->createDocumentManager(array(__DIR__));
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

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetScheduledReorders()
    {
        $this->uow->getScheduledReorders();
    }
}

