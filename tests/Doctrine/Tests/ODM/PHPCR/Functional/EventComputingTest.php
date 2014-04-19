<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;

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

    protected $localePrefs = array(
        'en' => array('de', 'fr'),
        'fr' => array('de', 'en'),
        'de' => array('en'),
        'it' => array('fr', 'de', 'en'),
    );

    public function setUp()
    {
        $this->listener = new TestEventDocumentChanger();
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
        $this->dm->getEventManager()->addEventListener(array('prePersist', 'postPersist', 'preUpdate', 'postUpdate', 'preMove', 'postMove', 'bindTranslation'), $this->listener);
        $this->dm->setLocaleChooserStrategy(new LocaleChooser($this->localePrefs, 'en'));
    }

    public function testComputingBetweenEvents()
    {
        // Create initial user
        $user = new \Doctrine\Tests\Models\CMS\CmsUserTranslatable();
        $user->name = 'mdekrijger';
        $user->username = 'mdekrijger';
        $user->status = 'active';

        // In prepersist the name will be changed
        // In postpersist the username will be changed
        $this->dm->persist($user);

        // bindTranslation event should change the name to bindTranslation
        $this->dm->bindTranslation($user, 'en');
        $this->assertEquals('bindTranslation', $user->username);

        $this->dm->flush();
        $this->dm->clear();

        // Post persist data is not saved to document, so check before reloading document
        $this->assertTrue($user->username=='postpersist');

        // Be sure that document is really saved by refetching it from ODM
        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUserTranslatable', $user->id);
        $this->assertEquals('prepersist', $user->name);

        // Change document
        // In preupdate the name will be changed
        // In postupdate the username will be changed
        $user->status = 'changed';
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        // Post persist data is not saved to document, so check before reloading document
        $this->assertEquals('postupdate', $user->username);

        // Be sure that document is really saved by refetching it from ODM
        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUserTranslatable', $user->id);
        $this->assertEquals('preupdate', $user->name);

        // Move from /functional/preudpate to /functional/moved
        $targetPath = '/functional/moved';
        $this->dm->move($user, $targetPath);
        $this->dm->flush();
        // we overwrote the name and username fields during the move event, so the object changed
        $this->assertEquals('premove', $user->name);
        $this->assertEquals('premove-postmove', $user->username);

        $this->dm->clear();


        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUserTranslatable', $targetPath);

        // the document was moved but the only changes applied in preUpdate are persisted,
        // pre/postMove changes are not persisted in that flush
        $this->assertEquals('preupdate', $user->name);
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
    public $bindTranslation = false;

    public function prePersist(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        $document->name = 'prepersist';
    }

    public function postPersist(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        $document->username = 'postpersist';
    }

    public function preUpdate(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        $document->name = 'preupdate';
    }

    public function postUpdate(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        $document->username = 'postupdate';
    }

    public function preMove(LifecycleEventArgs $e)
    {
        $this->preMove = true;
        $document = $e->getObject();
        $document->name = 'premove'; // I try to update the name of the document but after move, the document should never be modified
        $document->username = 'premove';
    }

    public function postMove(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        $document->username .= '-postmove';
    }

    public function bindTranslation(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        $document->username = 'bindTranslation';
    }
}