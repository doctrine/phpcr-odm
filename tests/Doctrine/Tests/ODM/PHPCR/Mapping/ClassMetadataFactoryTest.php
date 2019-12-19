<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\BarfooRepository;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ChildClassesAndLeafObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ClassInheritanceChildMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ClassInheritanceChildOverridesMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\DefaultMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\DocumentRepository;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\InheritedMixinMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceableChildReferenceableFalseMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\TranslatorMappingObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\TranslatorMappingObjectNoStrategy;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\UuidMappingObjectNotReferenceable;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;

class ClassMetadataFactoryTest extends TestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    protected function getMetadataFor(string $fqn): ClassMetadata
    {
        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);
        $annotationDriver->addPaths([__DIR__.'/Model']);
        $this->dm->getConfiguration()->setMetadataDriverImpl($annotationDriver);

        $cmf = new ClassMetadataFactory($this->dm);

        return $cmf->getMetadataFor($fqn);
    }

    public function setUp(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $this->dm = DocumentManager::create($session);
    }

    public function testNotMappedThrowsException()
    {
        $cmf = new ClassMetadataFactory($this->dm);

        $this->expectException(MappingException::class);
        $cmf->getMetadataFor('unknown');
    }

    public function testGetMapping()
    {
        $cmf = new ClassMetadataFactory($this->dm);

        $cm = new ClassMetadata('stdClass');

        $cmf->setMetadataFor('stdClass', $cm);

        $this->assertTrue($cmf->hasMetadataFor('stdClass'));
        $this->assertSame($cm, $cmf->getMetadataFor('stdClass'));
    }

    public function testGetAllMetadata()
    {
        $driver = new PHPDriver([__DIR__.'/Model/php']);
        $this->dm->getConfiguration()->setMetadataDriverImpl($driver);

        $cmf = new ClassMetadataFactory($this->dm);

        $cm = new ClassMetadata('stdClass');
        $cmf->setMetadataFor('stdClass', $cm);

        $metadata = $cmf->getAllMetadata();

        $this->assertTrue(is_array($metadata));
    }

    public function testCacheDriver()
    {
        $this->markTestIncomplete('Test cache driver setting and handling.');
    }

    public function testLoadMetadataReferenceableChildOverriddenAsFalse()
    {
        $this->expectException(MappingException::class);
        $this->getMetadataFor(ReferenceableChildReferenceableFalseMappingObject::class);
    }

    public function testLoadMetadataDefaults()
    {
        $meta = $this->getMetadataFor(DefaultMappingObject::class);
        $this->assertFalse($meta->referenceable);
        $this->assertNull($meta->translator);
        $this->assertEquals('nt:unstructured', $meta->nodeType);
        $this->assertFalse($meta->versionable);
        $this->assertNull($meta->customRepositoryClassName);
    }

    public function testLoadMetadataClassInheritanceChild()
    {
        $meta = $this->getMetadataFor(ClassInheritanceChildMappingObject::class);
        $this->assertTrue($meta->referenceable);
        $this->assertEquals('foo', $meta->translator);
        $this->assertEquals('nt:test', $meta->nodeType);
        $this->assertEquals(['mix:foo', 'mix:bar'], $meta->mixins);
        $this->assertEquals('simple', $meta->versionable);
        $this->assertEquals(DocumentRepository::class, $meta->customRepositoryClassName);
    }

    public function testLoadInheritedMixins()
    {
        $meta = $this->getMetadataFor(InheritedMixinMappingObject::class);
        $this->assertCount(2, $meta->mixins);
        $this->assertContains('mix:lastModified', $meta->mixins);
        $this->assertContains('mix:title', $meta->mixins);
    }

    public function testLoadMetadataClassInheritanceChildCanOverride()
    {
        $meta = $this->getMetadataFor(ClassInheritanceChildOverridesMappingObject::class);
        $this->assertTrue($meta->referenceable);
        $this->assertEquals('bar', $meta->translator);
        $this->assertEquals('nt:test-override', $meta->nodeType);
        $this->assertEquals(['mix:baz'], $meta->mixins);
        $this->assertEquals('full', $meta->versionable);
        $this->assertEquals(BarfooRepository::class, $meta->customRepositoryClassName);
    }

    public function testValidateUuidNotReferenceable()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('is not referenceable');
        $this->getMetadataFor(UuidMappingObjectNotReferenceable::class);
    }

    public function testValidateTranslatableNoStrategy()
    {
        $this->expectException(MappingException::class);
        $this->getMetadataFor(TranslatorMappingObjectNoStrategy::class);
    }

    public function testValidateChildClassesIfLeafConflict()
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Cannot map a document as a leaf and define child classes for "Doctrine\Tests\ODM\PHPCR\Mapping\Model\ChildClassesAndLeafObject"');
        $this->getMetadataFor(ChildClassesAndLeafObject::class);
    }

    public function testValidateTranslatable()
    {
        $this->getMetadataFor(TranslatorMappingObject::class);
    }

    public function testLoadClassMetadataEvent()
    {
        $listener = new Listener();
        $evm = $this->dm->getEventManager();
        $evm->addEventListener([Event::loadClassMetadata], $listener);

        $meta = $this->getMetadataFor(DefaultMappingObject::class);
        $this->assertTrue($listener->called);
        $this->assertSame($this->dm, $listener->dm);
        $this->assertSame($meta, $listener->meta);
    }
}

class Listener
{
    public $dm;

    public $meta;

    public $called = false;

    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        $this->called = true;
        $this->dm = $args->getObjectManager();
        $this->meta = $args->getClassMetadata();
    }
}
