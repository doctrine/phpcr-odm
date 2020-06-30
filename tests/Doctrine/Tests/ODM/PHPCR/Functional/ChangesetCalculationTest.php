<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsUserTranslatable;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;

class ChangesetCalculationTest extends PHPCRFunctionalTestCase
{
    /**
     * @var ChangesetListener
     */
    private $listener;

    /**
     * @var DocumentManager
     */
    private $dm;

    public function setUp(): void
    {
        $this->listener = new ChangesetListener();
        $this->dm = $this->createDocumentManager();
        $this->dm->setLocaleChooserStrategy(new LocaleChooser(['en' => ['fr'], 'fr' => ['en']], 'en'));
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testComputeChangeset()
    {
        $this->dm
            ->getEventManager()
            ->addEventListener(
                [
                    Event::postUpdate,
                ],
                $this->listener
            );

        // Create initial user
        $user1 = new CmsUser();
        $user1->name = 'david';
        $user1->username = 'dbu';
        $user1->status = 'active';
        $this->dm->persist($user1);

        // Create additional user
        $user2 = new CmsUser();
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
                 [
                     Event::postUpdate,
                 ],
                 $this->listener
             );

        // Create initial user
        $user1 = new CmsUserTranslatable();
        $user1->name = 'david';
        $user1->username = 'dbu';
        $user1->status = 'active';
        $this->dm->persist($user1);

        // Create additional user
        $user2 = new CmsUserTranslatable();
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
                [
                    Event::postUpdate,
                ],
                $this->listener
            );

        // Create initial user
        $user1 = new CmsUserTranslatable();
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
        ++$this->count;
    }
}
