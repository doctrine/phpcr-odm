<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\Event\ManagerEventArgs;
use Doctrine\ODM\PHPCR\Event\MoveEventArgs;

use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Doctrine\Tests\Models\CMS\CmsPage;

class EventManagerResetTest extends PHPCRFunctionalTestCase
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
        $this->listener = new TestResetListener();
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
        $this->dm->getEventManager()->addEventListener(array(
            'prePersist', 'postPersist', 'preUpdate', 'postUpdate',
        ), $this->listener);
    }

    public function testResetEvents()
    {
        $page = new CmsPage();
        $page->title = "my-page";

        $pageContent = new CmsPageContent();
        $pageContent->id = 1;
        $pageContent->content = "long story";
        $pageContent->formatter = "plaintext";

        $page->content = $pageContent;

        $this->dm->persist($page);

        $this->assertEquals(serialize(array('id' => $pageContent->id)), $page->content);

        $this->dm->flush();

        $this->assertInstanceOf('Doctrine\Tests\ODM\PHPCR\Functional\CmsPageContent', $page->content);


        // This is required as the originalData in the UnitOfWork doesn’t set the node of the Document
        $this->dm->clear();

        $pageLoaded = $this->dm->getRepository('Doctrine\Tests\Models\CMS\CmsPage')->find($page->id);

        $pageLoaded->title = "my-page-changed";

        $this->assertEquals('my-page-changed', $pageLoaded->title);

        $this->dm->flush();

        $this->assertEquals('my-page', $pageLoaded->title);


        $pageLoaded->content = $pageContent;

        $this->dm->persist($pageLoaded);

        $this->dm->flush();

        $this->assertInstanceOf('Doctrine\Tests\ODM\PHPCR\Functional\CmsPageContent', $page->content);
    }
}

class TestResetListener
{

    public function prePersist(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        if ($document instanceof CmsPage && $document->content instanceof CmsPageContent){
            $contentReference = array('id' => $document->content->id);
            $document->content = serialize($contentReference);
        }
    }

    public function postPersist(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        if ($document instanceof CmsPage){
            $contentReference = unserialize($document->content);

            if ($contentReference !== false && isset($contentReference['id'])){

                // Load real object using $contentReference['id']
                $pageContent = new CmsPageContent();
                $pageContent->id = 1;

                $document->content = $pageContent;
            }
        }
    }

    public function preUpdate(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        if ($document instanceof CmsPage && $document->title !== 'my-page'){
            $document->title = 'my-page';
        }

        if ($document instanceof CmsPage && $document->content instanceof CmsPageContent){
            $contentReference = array('id' => $document->content->id);
            $document->content = serialize($contentReference);
        }
    }

    public function postUpdate(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        if ($document instanceof CmsPage){
            $contentReference = unserialize($document->content);

            if ($contentReference !== false && isset($contentReference['id'])){

                // Load real object using $contentReference['id']
                $pageContent = new CmsPageContent();
                $pageContent->id = 1;

                $document->content = $pageContent;
            }
        }
    }
}


class CmsPageContent
{
    public $id;
    public $content;
    public $formatter;
}
