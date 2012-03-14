<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Common\EventArgs;

class EventManagerTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    private $listener;
    private $dm;

    public function setUp()
    {
        $this->listener = new TestListener();
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
        $this->dm->getEventManager()->addEventListener(array('prePersist', 'postPersist', 'preUpdate', 'postUpdate', 'preRemove', 'postRemove', 'onFlush'), $this->listener);
    }

    public function testTriggerEvents()
    {
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->name = "beberlei";
        $user->username = "beberlei";
        $user->status = "active";

        $this->dm->persist($user);

        $this->assertTrue($this->listener->prePersist);

        $this->dm->flush();

        $this->assertTrue($this->listener->onFlush);
        $this->assertFalse($this->listener->preUpdate);
        $this->assertFalse($this->listener->postUpdate);
        $this->assertTrue($this->listener->postPersist);
        $this->assertFalse($this->listener->preRemove);
        $this->assertFalse($this->listener->postRemove);


        $user->status = "changed";
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertTrue($this->listener->preUpdate);
        $this->assertTrue($this->listener->postUpdate);

        $this->dm->remove($user);

        $this->assertTrue($this->listener->preRemove);
        $this->assertFalse($this->listener->postRemove);
        $this->assertTrue($this->dm->contains($user));

        $this->dm->flush();

        $this->assertFalse($this->dm->contains($user));
        $this->assertTrue($this->listener->postRemove);
    }
}

class TestListener
{
    public $prePersist = false;
    public $postPersist = false;
    public $preUpdate = false;
    public $postUpdate = false;
    public $preRemove = false;
    public $postRemove = false;
    public $onFlush = false;

    public function prePersist(EventArgs $e)
    {
        $this->prePersist = true;
    }

    public function postPersist(EventArgs $e)
    {
        $this->postPersist = true;
    }

    public function preUpdate(EventArgs $e)
    {
        $this->preUpdate = true;
    }

    public function postUpdate(EventArgs $e)
    {
        $this->postUpdate = true;
    }

    public function preRemove(EventArgs $e)
    {
        $this->preRemove = true;
    }

    public function postRemove(EventArgs $e)
    {
        $this->postRemove = true;
    }

    public function onFlush(EventArgs $e)
    {
        $this->onFlush = true;
    }
}
