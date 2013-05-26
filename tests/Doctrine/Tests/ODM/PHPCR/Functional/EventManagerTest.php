<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\Event\ManagerEventArgs;
use Doctrine\ODM\PHPCR\Event\MoveEventArgs;

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

    public function setUp()
    {
        $this->listener = new TestPersistenceListener();
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
        $this->dm->getEventManager()->addEventListener(array(
            'prePersist', 'postPersist', 'preUpdate', 'postUpdate',
            'preRemove', 'postRemove', 'onFlush', 'postFlush', 'preFlush',
            'preMove', 'postMove'
        ), $this->listener);
    }

    public function testTriggerEvents()
    {
        $page = new CmsPage();
        $page->title = "my-page";
        $page->content = "long story";

        $this->dm->persist($page);

        $this->assertTrue($this->listener->pagePrePersist);
        $this->assertFalse($this->listener->itemPrePersist);
        $this->assertFalse($this->listener->postFlush);
        $this->assertFalse($this->listener->preFlush);

        $this->dm->flush();

        $this->assertTrue($this->listener->onFlush);
        $this->assertTrue($this->listener->postFlush);
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

        $item = new CmsItem();
        $item->name = "my-item";
        $item->documentTarget = $page;

        $page->content = "short story";
        $this->dm->persist($item);
        $page->addItem($item);

        $this->dm->persist($page);
        $this->dm->flush();
        $this->assertTrue($this->listener->preUpdate);
        $this->assertTrue($this->listener->itemPrePersist);
        $this->assertTrue($this->listener->postUpdate);
        $this->assertTrue($this->listener->itemPostPersist);

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
    public $preFlush = false;
    public $itemPreMove = false;
    public $itemPostMove = false;
    public $pagePreMove = false;
    public $pagePostMove = false;

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

    public function preUpdate(LifecycleEventArgs $e)
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

    public function preFlush(ManagerEventArgs $e)
    {
        $this->preFlush = true;
    }
}
