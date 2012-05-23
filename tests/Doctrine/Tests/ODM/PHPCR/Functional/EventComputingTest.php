<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Common\EventArgs;

class EventComputingTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    private $listener;
    private $dm;

    public function setUp()
    {
        $this->listener = new TestEventDocumentChanger();
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
        $this->dm->getEventManager()->addEventListener(array('prePersist', 'postPersist', 'preUpdate', 'postUpdate'), $this->listener);
    }

    public function testTriggerEvents()
    {
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->name = 'mdekrijger';
        $user->username = 'mdekrijger';
        $user->status = 'active';

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertTrue($user->name=='prepersist');
        $this->assertTrue($user->username=='postpersist');

        $user->status = 'changed';
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertTrue($user->name=='preupdate');
        $this->assertTrue($user->username=='postupdate');

    }
}

class TestEventDocumentChanger
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
        $document = $e->getDocument();
        $document->name = 'prepersist';
    }

    public function postPersist(EventArgs $e)
    {
        $document = $e->getDocument();
        $document->name = 'postpersist';
    }

    public function preUpdate(EventArgs $e)
    {
        $document = $e->getDocument();
        $document->name = 'preupdate';

    }

    public function postUpdate(EventArgs $e)
    {
        $document = $e->getDocument();
        $document->name = 'postupdate';
    }

}