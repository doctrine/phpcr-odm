<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ODM\PHPCR\Event;
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
    }

    public function testComputingBetweenEvents()
    {
        $this->dm
             ->getEventManager()
             ->addEventListener(
                array(
                    Event::prePersist,
                    Event::postPersist,
                    Event::preUpdate,
                    Event::postUpdate,
                    Event::preMove,
                    Event::postMove
                ),
                $this->listener
            );

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
        $this->assertEquals('prepersist', $user->name);
        $this->assertEquals('active', $user->status);

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
        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        $this->assertEquals('preupdate', $user->name);
        $this->assertEquals('changed', $user->status);

        // Move from /functional/preudpate to /functional/moved
        $targetPath = '/functional/moved';
        $this->dm->move($user, $targetPath);
        $this->dm->flush();
        // we overwrote the name and username fields during the move event, so the object changed
        $this->assertEquals('premove', $user->name);
        $this->assertEquals('premove-postmove', $user->username);

        $this->dm->clear();


        $user = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $targetPath);

        // the document was moved but the only changes applied in preUpdate are persisted,
        // pre/postMove changes are not persisted in that flush
        $this->assertEquals('preupdate', $user->name);
        $this->assertTrue($this->listener->preMove);

        // Clean up
        $this->dm->remove($user);
        $this->dm->flush();
    }

    public function testComputingBetweenEventsWithTranslation()
    {
        $this->dm
             ->getEventManager()
             ->addEventListener(
                array(
                    Event::preCreateTranslation,
                    Event::postLoadTranslation,
                    Event::preRemoveTranslation,
                    Event::postRemoveTranslation,
                ),
                $this->listener
            );

        $this->dm->setLocaleChooserStrategy(new LocaleChooser($this->localePrefs, 'en'));
        // Create initial user
        $user = new \Doctrine\Tests\Models\CMS\CmsUserTranslatable();
        $user->name = 'mdekrijger';
        $user->username = 'mdekrijger';
        $user->status = 'active';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->findTranslation('Doctrine\Tests\Models\CMS\CmsUserTranslatable', $user->id, 'en');

        // username should be changed after loading the translation
        $this->assertEquals('loadTranslation', $user->username);

        // name had been changed pre binding translation
        $this->assertEquals('preCreateTranslation', $user->name);

        $this->dm->name = 'neuer Name';
        $this->dm->bindTranslation($user, 'de');
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->findTranslation('Doctrine\Tests\Models\CMS\CmsUserTranslatable', $user->id, 'en');

        $this->dm->removeTranslation($user, 'en');
        $this->assertEquals('preRemoveTranslation', $user->name);

        $this->dm->flush();
        $this->dm->clear();

        $this->assertEquals('postRemoveTranslation', $user->username);
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

    public function preCreateTranslation(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        $document->name = 'preCreateTranslation';
    }

    public function postLoadTranslation(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        $document->username = 'loadTranslation';
    }

    public function preRemoveTranslation(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        $document->name = 'preRemoveTranslation';
    }

    public function postRemoveTranslation(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        $document->username = 'postRemoveTranslation';
    }
}
