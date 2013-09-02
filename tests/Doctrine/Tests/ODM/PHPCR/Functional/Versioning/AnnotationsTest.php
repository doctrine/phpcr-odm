<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Versioning;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory,
    Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class AnnotationsTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{

    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * @var \PHPCR\SessionInterface
     */
    private $session;

    public function setup()
    {
        $this->dm = $this->createDocumentManager();
        $this->session = $this->dm->getPhpcrSession();
    }

    /**
     * Test the annotations pertaining to versioning are correctly loaded.
     */
    public function testLoadAnnotations()
    {
        $factory = new ClassMetadataFactory($this->dm);

        // Check the annotation is correctly read if it is present
        $metadata = $factory->getMetadataFor('Doctrine\Tests\Models\Versioning\VersionableArticle');
        $this->assertTrue(isset($metadata->versionable));
        $this->assertEquals('simple', $metadata->versionable);

        // Check the annotation is not set if it is not present
        $metadata = $factory->getMetadataFor('Doctrine\Tests\Models\Versioning\NonVersionableArticle');
        $this->assertTrue(isset($metadata->versionable));
        $this->assertEquals(false, $metadata->versionable);
    }

    /**
     * Test that using an invalid versionable annotation will not work
     * @expectedException \Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testLoadInvalidAnnotation()
    {
        $factory = new ClassMetadataFactory($this->dm);
        $metadata = $factory->getMetadataFor('Doctrine\Tests\Models\Versioning\InvalidVersionableArticle');
    }

    /**
     * Test that using the Version annotation on non-versionable documents will not work
     * @expectedException \Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testLoadInconsistentAnnotations()
    {
        $factory = new ClassMetadataFactory($this->dm);
        $factory->getMetadataFor('Doctrine\Tests\Models\Versioning\InconsistentVersionableArticle');
    }

    /**
     * Check that persisting a node with the versionable type will add the correct mixin to the node
     */
    public function testAnnotationOnPersist()
    {
        $repository = $this->dm->getPhpcrSession()->getRepository();
        if (!$repository->getDescriptor('option.versioning.supported')) {
            $this->markTestSkipped('PHPCR repository doesn\'t support versioning');
        }

        $node = $this->createTestDocument('versionable-article-test', 'Doctrine\\Tests\\Models\\Versioning\\VersionableArticle');
        $this->assertTrue($node->isNodeType('mix:simpleVersionable'));
        $this->assertFalse($node->isNodeType('mix:versionable'));

        // mix:versionable derives from mix:simpleVersionable, so a full versionable node will be both types
        $node = $this->createTestDocument('versionable-article-test', 'Doctrine\\Tests\\Models\\Versioning\\FullVersionableArticle');
        $this->assertTrue($node->isNodeType('mix:versionable'));
        $this->assertTrue($node->isNodeType('mix:simpleVersionable'));

        $node = $this->createTestDocument('versionable-article-test', 'Doctrine\\Tests\\Models\\Versioning\\NonVersionableArticle');
        $this->assertFalse($node->isNodeType('mix:simpleVersionable'));
        $this->assertFalse($node->isNodeType('mix:versionable'));

        $node = $this->createTestDocument('versionable-article-test', 'Doctrine\\Tests\\Models\\Versioning\\ExtendedVersionableArticle');
        $this->assertTrue($node->isNodeType('mix:simpleVersionable'));
        $this->assertTrue($node->isNodeType('mix:versionable'));
    }

    /**
     * Create a document, save it, and return the underlying PHPCR node.
     *
     * @param string $name The name of the new node (will be created under root)
     * @param string $class The class name of the document
     *
     * @return \PHPCR\NodeInterface
     */
    protected function createTestDocument($name, $class)
    {
        $this->removeTestNode($name);

        $article = new $class();
        $article->id = '/' . $name;
        $article->author = 'John Doe';
        $article->topic = 'Some topic';
        $article->topic = 'Some subject';
        $article->setText('Lorem ipsum...');

        $this->dm->persist($article);
        $this->dm->flush();

        $node = $this->session->getNode('/' . $name);

        return $node;
    }

    /**
     * Remove a PHPCR node under the root node
     *
     * @param string $name The name of the node to remove
     *
     * @return void
     */
    protected function removeTestNode($name)
    {
        $root = $this->session->getNode('/');
        if ($root->hasNode($name)) {
            $root->getNode($name)->remove();
            $this->session->save();
            $this->dm->clear();
        }
    }
}
