<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

class EventManagerTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    private $listener;
    private $dm;

    public function setUp()
    {
        $this->listener = new TestListener();
        $this->dm = $this->createDocumentManager();
        $this->dm->getEventManager()->addEventListener(array('prePersist', 'preUpdate', 'postUpdate', 'preRemove', 'postRemove', 'onFlush'), $this->listener);
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
        $this->assertTrue($this->listener->preUpdate);
        $this->assertTrue($this->listener->postUpdate);
        $this->assertFalse($this->listener->preRemove);
        $this->assertFalse($this->listener->postRemove);

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
    public $preUpdate = false;
    public $postUpdate = false;
    public $preRemove = false;
    public $postRemove = false;
    public $onFlush = false;

    public function prePersist()
    {
        $this->prePersist = true;
    }

    public function preUpdate()
    {
        $this->preUpdate = true;
    }

    public function postUpdate()
    {
        $this->postUpdate = true;
    }

    public function preRemove()
    {
        $this->preRemove = true;
    }

    public function postRemove()
    {
        $this->postRemove = true;
    }

    public function onFlush()
    {
        $this->onFlush = true;
    }
}