<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

class OptimisticLockingTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    public function testAccessToRevisionThroughVersionField()
    {
        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->text = "Lorem Ipsum dolor sunt.";
        $article->topic = "Lorem!";

        $dm = $this->createDocumentManager();
        $dm->persist($article);
        $dm->flush();
        $dm->clear();

        $article = $dm->find('Doctrine\Tests\Models\CMS\CmsArticle', $article->id);
        $this->assertNotNull($article->version);
        $this->assertEquals($article->version, $dm->getUnitOfWork()->getDocumentRevision($article));
    }

    public function testPersistVersionedDocumentUpdatesVersionField()
    {
        $article = new \Doctrine\Tests\Models\CMS\CmsArticle();
        $article->text = "Lorem Ipsum dolor sunt.";
        $article->topic = "Lorem!";

        $dm = $this->createDocumentManager();
        $dm->persist($article);
        $dm->flush();

        $this->assertNotNull($article->version);
        $this->assertEquals($article->version, $dm->getUnitOfWork()->getDocumentRevision($article));
    }
}