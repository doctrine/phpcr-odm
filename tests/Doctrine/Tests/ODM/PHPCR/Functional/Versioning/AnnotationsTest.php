<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Versioning;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\Tests\Models\Versioning\ExtendedVersionableArticle;
use Doctrine\Tests\Models\Versioning\FullVersionableArticle;
use Doctrine\Tests\Models\Versioning\InconsistentVersionableArticle;
use Doctrine\Tests\Models\Versioning\InvalidVersionableArticle;
use Doctrine\Tests\Models\Versioning\NonVersionableArticle;
use Doctrine\Tests\Models\Versioning\VersionableArticle;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;

class AnnotationsTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var SessionInterface
     */
    private $session;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager();
        $this->session = $this->dm->getPhpcrSession();
    }

    /**
     * Test the annotations pertaining to versioning are correctly loaded.
     */
    public function testLoadAnnotations(): void
    {
        $factory = new ClassMetadataFactory($this->dm);

        // Check the annotation is correctly read if it is present
        $metadata = $factory->getMetadataFor(VersionableArticle::class);
        $this->assertInstanceOf(ClassMetadata::class, $metadata);
        $this->assertEquals('simple', $metadata->versionable);

        // Check the annotation is not set if it is not present
        $metadata = $factory->getMetadataFor(NonVersionableArticle::class);
        $this->assertInstanceOf(ClassMetadata::class, $metadata);
        $this->assertFalse($metadata->versionable);
    }

    /**
     * Test that using an invalid versionable annotation will not work.
     */
    public function testLoadInvalidAnnotation(): void
    {
        $factory = new ClassMetadataFactory($this->dm);

        $this->expectException(MappingException::class);
        $factory->getMetadataFor(InvalidVersionableArticle::class);
    }

    /**
     * Test that using the Version annotation on non-versionable documents will not work.
     */
    public function testLoadInconsistentAnnotations(): void
    {
        $factory = new ClassMetadataFactory($this->dm);

        $this->expectException(MappingException::class);
        $factory->getMetadataFor(InconsistentVersionableArticle::class);
    }

    /**
     * Check that persisting a node with the versionable type will add the correct mixin to the node.
     */
    public function testAnnotationOnPersist(): void
    {
        $repository = $this->dm->getPhpcrSession()->getRepository();
        if (!$repository->getDescriptor('option.versioning.supported')) {
            $this->markTestSkipped('PHPCR repository doesn\'t support versioning');
        }

        $node = $this->createTestDocument('versionable-article-test', VersionableArticle::class);
        $this->assertTrue($node->isNodeType('mix:simpleVersionable'));
        $this->assertFalse($node->isNodeType('mix:versionable'));

        // mix:versionable derives from mix:simpleVersionable, so a full versionable node will be both types
        $node = $this->createTestDocument('versionable-article-test', FullVersionableArticle::class);
        $this->assertTrue($node->isNodeType('mix:versionable'));
        $this->assertTrue($node->isNodeType('mix:simpleVersionable'));

        $node = $this->createTestDocument('versionable-article-test', NonVersionableArticle::class);
        $this->assertFalse($node->isNodeType('mix:simpleVersionable'));
        $this->assertFalse($node->isNodeType('mix:versionable'));

        $node = $this->createTestDocument('versionable-article-test', ExtendedVersionableArticle::class);
        $this->assertTrue($node->isNodeType('mix:simpleVersionable'));
        $this->assertTrue($node->isNodeType('mix:versionable'));
    }

    /**
     * Create a document, save it, and return the underlying PHPCR node.
     *
     * @param string $name  The name of the new node (will be created under root)
     * @param string $class The class name of the document
     */
    private function createTestDocument(string $name, string $class): NodeInterface
    {
        $this->removeTestNode($name);

        $article = new $class();
        $article->id = '/'.$name;
        $article->author = 'John Doe';
        $article->topic = 'Some subject';
        $article->setText('Lorem ipsum...');

        $this->dm->persist($article);
        $this->dm->flush();

        return $this->session->getNode('/'.$name);
    }

    /**
     * Remove a PHPCR node under the root node.
     */
    protected function removeTestNode(string $name): void
    {
        $root = $this->session->getNode('/');
        if ($root->hasNode($name)) {
            $root->getNode($name)->remove();
            $this->session->save();
            $this->dm->clear();
        }
    }
}
