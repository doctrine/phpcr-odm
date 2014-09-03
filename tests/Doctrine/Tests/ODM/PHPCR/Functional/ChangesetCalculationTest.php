<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;

class ChangesetCalculationTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var ChangesetListener
     */
    private $listener;

    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    public function setUp()
    {
        $this->listener = new ChangesetListener();
        $this->dm = $this->createDocumentManager();
        $this->dm->setLocaleChooserStrategy(new LocaleChooser(array('en' => array('fr'), 'fr' => array('en')), 'en'));
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testComputeChangeset()
    {
        $this->dm
            ->getEventManager()
            ->addEventListener(
                array(
                    Event::postUpdate,
                ),
                $this->listener
            );

        // Create initial user
        $user1 = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user1->name = 'david';
        $user1->username = 'dbu';
        $user1->status = 'active';
        $this->dm->persist($user1);

        // Create additional user
        $user2 = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user2->name = 'lukas';
        $user2->username = 'lsmith';
        $user2->status = 'active';
        $this->dm->persist($user2);

        $this->dm->flush();
        $this->assertEquals(0, $this->listener->count);
        $this->dm->clear();

        $user1 = $this->dm->find(null, $user1->id);
        $this->dm->find(null, $user2->id);

        $user1->status = 'changed';
        $this->dm->flush();

        $this->assertEquals(1, $this->listener->count);
    }

    public function testComputeChangesetTranslatable()
    {
        $this->dm
             ->getEventManager()
             ->addEventListener(
                array(
                    Event::postUpdate,
                ),
                $this->listener
            );

        // Create initial user
        $user1 = new \Doctrine\Tests\Models\CMS\CmsUserTranslatable();
        $user1->name = 'david';
        $user1->username = 'dbu';
        $user1->status = 'active';
        $this->dm->persist($user1);

        // Create additional user
        $user2 = new \Doctrine\Tests\Models\CMS\CmsUserTranslatable();
        $user2->name = 'lukas';
        $user2->username = 'lsmith';
        $user2->status = 'active';
        $this->dm->persist($user2);

        $this->dm->flush();
        $this->assertEquals(0, $this->listener->count);
        $this->dm->clear();

        $user1 = $this->dm->find(null, $user1->id);
        $this->dm->find(null, $user2->id);

        $user1->status = 'changed';
        $this->dm->flush();

        $this->assertEquals(1, $this->listener->count);
    }

    public function testComputeChangesetTranslatableFind()
    {
        $this->dm
            ->getEventManager()
            ->addEventListener(
                array(
                    Event::postUpdate,
                ),
                $this->listener
            );

        // Create initial user
        $user1 = new \Doctrine\Tests\Models\CMS\CmsUserTranslatable();
        $user1->name = 'david';
        $user1->username = 'dbu';
        $user1->status = 'activ';
        $this->dm->persist($user1);
        $this->dm->bindTranslation($user1, 'en');
        $user1->status = 'actif';
        $this->dm->bindTranslation($user1, 'fr');

        $this->dm->flush();
        $this->assertEquals(0, $this->listener->count);
        $this->dm->clear();

        $user1 = $this->dm->findTranslation(null, $user1->id, 'en');
        $this->dm->findTranslation(null, $user1->id, 'fr');

        $this->dm->flush();

        $this->assertEquals(0, $this->listener->count);

        $user1 = $this->dm->findTranslation(null, $user1->id, 'en');
        $user1->status = 'active';
        $this->dm->findTranslation(null, $user1->id, 'fr');

        $this->dm->flush();

        $this->assertEquals(1, $this->listener->count);
        $this->dm->clear();

        $user1 = $this->dm->findTranslation(null, $user1->id, 'en');
        $this->assertEquals('active', $user1->status);

    }
}

class ChangesetListener
{
    public $count = 0;

    public function postUpdate(LifecycleEventArgs $e)
    {
        $this->count++;
    }
}
