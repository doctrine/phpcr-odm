<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\Event\ManagerEventArgs;
use Doctrine\ODM\PHPCR\Event\PreUpdateEventArgs;

use Doctrine\ODM\PHPCR\Event\MoveEventArgs;

use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;
use Doctrine\Tests\Models\CMS\CmsPageTranslatable;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Doctrine\Tests\Models\CMS\CmsPage;
use Doctrine\Tests\Models\CMS\CmsItem;

class EventManagerTest extends PHPCRFunctionalTestCase
{
    /**
     * @var TestPersistenceListener
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
        $this->listener = new TestPersistenceListener();
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testTriggerEvents()
    {
        $this->dm
            ->getEventManager()
            ->addEventListener(
                array(
                    Event::prePersist,
                    Event::postPersist,
                    Event::preUpdate,
                    Event::postUpdate,
                    Event::preRemove,
                    Event::postRemove,
                    Event::onFlush,
                    Event::postFlush,
                    Event::preFlush,
                    Event::preMove,
                    Event::postMove,
                    Event::endFlush,
                ),
                $this->listener
            );

        $page = new CmsPage();
        $page->title = "my-page";
        $page->content = "long story";

        $this->dm->persist($page);

        $this->assertTrue($this->listener->pagePrePersist);
        $this->assertFalse($this->listener->itemPrePersist);
        $this->assertFalse($this->listener->postFlush);
        $this->assertFalse($this->listener->endFlush);
        $this->assertFalse($this->listener->preFlush);

        $this->dm->flush();

        $this->assertTrue($this->listener->onFlush);
        $this->assertTrue($this->listener->postFlush);
        $this->assertTrue($this->listener->endFlush);
        $this->assertTrue($this->listener->preFlush);
        $this->assertFalse($this->listener->preUpdate);
        $this->assertFalse($this->listener->postUpdate);
        $this->assertTrue($this->listener->pagePostPersist);
        $this->assertFalse($this->listener->itemPostPersist);
        $this->assertFalse($this->listener->pagePreRemove);
        $this->assertFalse($this->listener->pagePostRemove);
        $this->assertFalse($this->listener->itemPreRemove);
        $this->assertFalse($this->listener->pagePreMove);
        $this->assertFalse($this->listener->pagePostMove);
        $this->assertFalse($this->listener->itemPreMove);
        $this->assertFalse($this->listener->itemPostMove);

        $this->dm->move($page, '/functional/moved-' . $page->title);

        $this->assertFalse($this->listener->pagePreMove);
        $this->assertFalse($this->listener->pagePostMove);

        $this->dm->flush();

        $this->assertTrue($this->listener->pagePreMove);
        $this->assertTrue($this->listener->pagePostMove);
        $this->assertFalse($this->listener->itemPreMove);
        $this->assertFalse($this->listener->itemPostMove);

        $this->dm->flush();

        $item = new CmsItem();
        $item->name = "my-item";
        $item->documentTarget = $page;

        $page->content = "short story";
        $this->dm->persist($item);
        $page->addItem($item);

        $this->dm->flush();
        $this->assertTrue($this->listener->preUpdate);
        $this->assertTrue($this->listener->itemPrePersist);
        $this->assertTrue($this->listener->postUpdate);
        $this->assertTrue($this->listener->itemPostPersist);
        $this->assertEquals('long story is now short story', $page->content);

        $pageId = $this->dm->getUnitOfWork()->getDocumentId($page);
        $itemId = $this->dm->getUnitOfWork()->getDocumentId($item);
        $this->dm->clear();

        $page = $this->dm->find(null, $pageId);
        $item = $this->dm->find(null, $itemId);
        $this->assertEquals('long story is now short story', $page->content);

        $this->dm->remove($item);
        $this->dm->remove($page);

        $this->assertTrue($this->listener->pagePreRemove);
        $this->assertTrue($this->listener->itemPreRemove);
        $this->assertFalse($this->listener->pagePostRemove);
        $this->assertFalse($this->listener->itemPostRemove);
        $this->assertFalse($this->dm->contains($page));
        $this->assertFalse($this->dm->contains($item));

        $this->dm->flush();

        $this->assertFalse($this->dm->contains($page));
        $this->assertFalse($this->dm->contains($item));
        $this->assertTrue($this->listener->pagePostRemove);
        $this->assertTrue($this->listener->itemPostRemove);
    }

    public function testTriggerTranslationEvents()
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

        $page = new CmsPageTranslatable();
        $page->title = "my-page";
        $page->content = "long story";

        $this->dm->persist($page);
        $this->assertFalse($this->listener->preCreateTranslation);
        $this->assertFalse($this->listener->postLoadTranslation);
        $this->assertFalse($this->listener->postRemoveTranslation);
        $this->assertFalse($this->listener->postRemoveTranslation);

        $this->dm->bindTranslation($page, 'en');

        $this->assertTrue($this->listener->preCreateTranslation);
        $this->assertFalse($this->listener->postLoadTranslation);
        $this->assertFalse($this->listener->postRemoveTranslation);

        $this->dm->flush();
        $this->dm->clear();

        $page = $this->dm->findTranslation('Doctrine\Tests\Models\CMS\CmsPageTranslatable', $page->id, 'en');

        $this->assertTrue($this->listener->postLoadTranslation);

        $page->title = 'neuer Titel';
        $this->dm->bindTranslation($page, 'de');

        $this->dm->flush();

        $this->dm->removeTranslation($page, 'en');

        $this->assertFalse($this->listener->postRemoveTranslation);
        $this->assertTrue($this->listener->preRemoveTranslation);
        $this->dm->flush();

        $this->assertTrue($this->listener->postRemoveTranslation);
    }
}

class TestPersistenceListener
{
    public $pagePrePersist = false;
    public $pagePostPersist = false;
    public $itemPrePersist = false;
    public $itemPostPersist = false;
    public $preUpdate = false;
    public $postUpdate = false;
    public $pagePreRemove = false;
    public $pagePostRemove = false;
    public $itemPreRemove = false;
    public $itemPostRemove = false;
    public $onFlush = false;
    public $postFlush = false;
    public $endFlush = false;
    public $preFlush = false;
    public $itemPreMove = false;
    public $itemPostMove = false;
    public $pagePreMove = false;
    public $pagePostMove = false;

    public $postLoadTranslation = false;
    public $preCreateTranslation = false;
    public $preRemoveTranslation = false;
    public $postRemoveTranslation = false;

    public function prePersist(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        if ($document instanceof CmsPage){
            $this->pagePrePersist = true;
        } elseif ($document instanceof CmsItem){
            $this->itemPrePersist = true;
        }
    }

    public function postPersist(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        if ($document instanceof CmsPage){
            $this->pagePostPersist = true;
        } elseif ($document instanceof CmsItem){
            $this->itemPostPersist = true;
        }
    }

    public function preUpdate(PreUpdateEventArgs $e)
    {
        $document = $e->getObject();
        if (! $document instanceof CmsPage ){
            return;
        }
        $dm = $e->getObjectManager();

        foreach ($document->getItems() as $item) {
            $dm->persist($item);
        }
        $this->preUpdate = true;

        if ($e->hasChangedField('content')) {
            $e->getObject()->content = $e->getOldValue('content').' is now '.$e->getNewValue('content');
            $e->setNewValue('content', $e->getObject()->content);
        }
    }

    public function postUpdate(LifecycleEventArgs $e)
    {
        $this->postUpdate = true;
    }

    public function preRemove(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        if ($document instanceof CmsPage){
            $this->pagePreRemove = true;
        } elseif ($document instanceof CmsItem){
            $this->itemPreRemove = true;
        }
    }

    public function postRemove(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        if ($document instanceof CmsPage){
            $this->pagePostRemove = true;
        } elseif ($document instanceof CmsItem){
            $this->itemPostRemove = true;
        }
    }

    public function preMove(MoveEventArgs $e)
    {
        $document = $e->getObject();
        if ($document instanceof CmsPage){
            $this->pagePreMove = true;
        } elseif ($document instanceof CmsItem){
            $this->itemPreMove = true;
        }
    }

    public function postMove(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        if ($document instanceof CmsPage){
            $this->pagePostMove = true;
        } elseif ($document instanceof CmsItem){
            $this->itemPostMove = true;
        }
    }

    public function onFlush(ManagerEventArgs $e)
    {
        $this->onFlush = true;
    }

    public function postFlush(ManagerEventArgs $e)
    {
        $this->postFlush = true;
    }

    public function endFlush(ManagerEventArgs $e)
    {
        $this->endFlush = true;
        $dm = $e->getObjectManager();

        // endFlush can call ->flush(). The UOW should exit early if there is nothing
        // to do, avoiding an infinite recursion.
        $dm->flush();
    }

    public function preFlush(ManagerEventArgs $e)
    {
        $this->preFlush = true;
    }

    public function preCreateTranslation(LifecycleEventArgs $e)
    {
        $this->preCreateTranslation = true;
    }

    public function postLoadTranslation(LifecycleEventArgs $e)
    {
        $this->postLoadTranslation = true;
    }

    public function preRemoveTranslation(LifecycleEventArgs $e)
    {
        $this->preRemoveTranslation = true;
    }

    public function postRemoveTranslation(LifecycleEventArgs $e)
    {
        $this->postRemoveTranslation = true;
    }
}
