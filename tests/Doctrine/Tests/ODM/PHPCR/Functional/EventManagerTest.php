<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Common\EventArgs;

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
        $this->dm->getEventManager()->addEventListener(array('prePersist', 'postPersist', 'preUpdate', 'postUpdate', 'preRemove', 'postRemove', 'onFlush'), $this->listener);
    }

    public function testTriggerEvents()
    {
        $page = new CmsPage();
        $page->title = "my-page";
        $page->content = "long story";

        $this->dm->persist($page);

        $this->assertTrue($this->listener->pagePrePersist);
        $this->assertFalse($this->listener->itemPrePersist);

        $this->dm->flush();

        $this->assertTrue($this->listener->onFlush);
        $this->assertFalse($this->listener->preUpdate);
        $this->assertFalse($this->listener->postUpdate);
        $this->assertTrue($this->listener->pagePostPersist);
        $this->assertFalse($this->listener->itemPostPersist);
        $this->assertFalse($this->listener->pagePreRemove);
        $this->assertFalse($this->listener->pagePostRemove);
        $this->assertFalse($this->listener->itemPreRemove);
        $this->assertFalse($this->listener->itemPostRemove);
        
        $item = new CmsItem();
        $item->name = "my-item";
        $item->documentTarget = $page;

        $page->content = "short story";
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
    
    public function prePersist(EventArgs $e)
    {
        $document = $e->getDocument();
        if ($document instanceof CmsPage){
            $this->pagePrePersist = true;
        } else if ($document instanceof CmsItem){
            $this->itemPrePersist = true;
        }
    }

    public function postPersist(EventArgs $e)
    {
        $document = $e->getDocument();
        if ($document instanceof CmsPage){
            $this->pagePostPersist = true;
        } else if ($document instanceof CmsItem){
            $this->itemPostPersist = true;
        }
    }

    public function preUpdate(EventArgs $e)
    {
        $document = $e->getDocument();
        if (! $document instanceof CmsPage ){
            return;
        }
        $dm = $e->getDocumentManager();

        foreach ($document->getItems() as $item) {
            $dm->persist($item);
        }
        $this->preUpdate = true;
    }

    public function postUpdate(EventArgs $e)
    {
        $this->postUpdate = true;
    }

    public function preRemove(EventArgs $e)
    {
        $document = $e->getDocument();
        if ($document instanceof CmsPage){
            $this->pagePreRemove = true;
        } else if ($document instanceof CmsItem){
            $this->itemPreRemove = true;
        }
    }

    public function postRemove(EventArgs $e)
    {
        $document = $e->getDocument();
        if ($document instanceof CmsPage){
            $this->pagePostRemove = true;
        } else if ($document instanceof CmsItem){
            $this->itemPostRemove = true;
        }
    }

    public function onFlush(EventArgs $e)
    {
        $this->onFlush = true;
    }
}
