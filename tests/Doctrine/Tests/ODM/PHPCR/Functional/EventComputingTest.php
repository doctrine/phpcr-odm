<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Common\EventArgs;

class EventComputingTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var TestEventDocumentChanger
     */
    private $listener;

    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    public function setUp()
    {
        $this->listener = new TestEventDocumentChanger();
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
        $this->dm->getEventManager()->addEventListener(array('prePersist', 'postPersist', 'preUpdate', 'postUpdate', 'preMove', 'postMove'), $this->listener);
    }

    public function testComputingBetweenEvents()
    {
        // Create initial user
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->name = 'mdekrijger';
        $user->username = 'mdekrijger';
        $user->status = 'active';

        // In prepersist the name will be changed
        // In postpersist the username will be changed
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        // Post persist data is not saved to document, so check before reloading document
        $this->assertTrue($user->username=='postpersist');

        // Be sure that document is really saved by refetching it from ODM
        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        $this->assertTrue($user->name=='prepersist');

        // Change document
        // In preupdate the name will be changed
        // In postupdate the username will be changed
        $user->status = 'changed';
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        // Post persist data is not saved to document, so check before reloading document
        $this->assertTrue($user->username=='postupdate');

        // Be sure that document is really saved by refetching it from ODM
        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        $this->assertTrue($user->name=='preupdate');

        // Move test, Before move the path is /functional/preudpate and I move to /preupdate
        $targetPath = '/' . $user->name;
        $this->dm->move($user, $targetPath);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($user->username == 'premove-postmove');

        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $targetPath);

        // The document is moved and do not be modified
        $this->assertTrue($user->name == 'preupdate');
        $this->assertTrue($this->listener->preMove);

        // Clean up
        $this->dm->remove($user);
        $this->dm->flush();
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
    public $preMove = false;
    public $postMove = false;
    public $onFlush = false;

    public function prePersist(EventArgs $e)
    {
        $document = $e->getDocument();
        $document->name = 'prepersist';
    }

    public function postPersist(EventArgs $e)
    {
        $document = $e->getDocument();
        $document->username = 'postpersist';
    }

    public function preUpdate(EventArgs $e)
    {
        $document = $e->getDocument();
        $document->name = 'preupdate';
    }

    public function postUpdate(EventArgs $e)
    {
        $document = $e->getDocument();
        $document->username = 'postupdate';
    }

    public function preMove(EventArgs $e)
    {
        $this->preMove = true;
        $document = $e->getDocument();
        $document->name = 'premove'; // I try to update the name of the document but after move, the document should never be modified
        $document->username = 'premove';
    }

    public function postMove(EventArgs $e)
    {
        $document = $e->getDocument();
        $document->username .= '-postmove';
    }
}