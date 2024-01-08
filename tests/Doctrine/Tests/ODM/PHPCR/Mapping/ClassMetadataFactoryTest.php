<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\Driver\PHPDriver;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClassMetadataFactoryTest extends TestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    protected function getMetadataFor(string $fqn): ClassMetadata
    {
        $attributeDriver = new AttributeDriver([__DIR__.'/Model']);
        $this->dm->getConfiguration()->setMetadataDriverImpl($attributeDriver);

        return (new ClassMetadataFactory($this->dm))->getMetadataFor($fqn);
    }

    public function setUp(): void
    {
        /** @var SessionInterface|MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $this->dm = DocumentManager::create($session);
    }

    public function testNotMappedThrowsException(): void
    {
        $cmf = new ClassMetadataFactory($this->dm);

        $this->expectException(MappingException::class);
        $cmf->getMetadataFor('unknown');
    }

    public function testGetMapping(): void
    {
        $cmf = new ClassMetadataFactory($this->dm);

        $cm = new ClassMetadata('stdClass');

        $cmf->setMetadataFor('stdClass', $cm);

        $this->assertTrue($cmf->hasMetadataFor('stdClass'));
        $this->assertSame($cm, $cmf->getMetadataFor('stdClass'));
    }

    public function testGetAllMetadata(): void
    {
        $driver = new PHPDriver([__DIR__.'/Model/php']);
        $this->dm->getConfiguration()->setMetadataDriverImpl($driver);

        $cmf = new ClassMetadataFactory($this->dm);

        $cm = new ClassMetadata('stdClass');
        $cmf->setMetadataFor('stdClass', $cm);

        $metadata = $cmf->getAllMetadata();

        $this->assertIsArray($metadata);
    }

    public function testCacheDriver(): void
    {
        $this->markTestIncomplete('Test cache driver setting and handling.');
    }

    public function testLoadMetadataReferenceableChildOverriddenAsFalse(): void
    {
        $this->expectException(MappingException::class);
        $this->getMetadataFor(ReferenceableChildReferenceableFalseMappingObject::class);
    }

    public function testLoadMetadataDefaults(): void
    {
        $meta = $this->getMetadataFor(DefaultMappingObject::class);
        $this->assertFalse($meta->referenceable);
        $this->assertNull($meta->translator);
        $this->assertEquals('nt:unstructured', $meta->nodeType);
        $this->assertFalse($meta->versionable);
        $this->assertNull($meta->customRepositoryClassName);
    }

    public function testLoadMetadataClassInheritanceChild(): void
    {
        $meta = $this->getMetadataFor(ClassInheritanceChildMappingObject::class);
        $this->assertTrue($meta->referenceable);
        $this->assertEquals('foo', $meta->translator);
        $this->assertEquals('nt:test', $meta->nodeType);
        $this->assertEquals(['mix:foo', 'mix:bar'], $meta->mixins);
        $this->assertEquals('simple', $meta->versionable);
        $this->assertEquals(DocumentRepository::class, $meta->customRepositoryClassName);
    }

    public function testLoadInheritedMixins(): void
    {
        $meta = $this->getMetadataFor(InheritedMixinMappingObject::class);
        $this->assertCount(2, $meta->mixins);
        $this->assertContains('mix:lastModified', $meta->mixins);
        $this->assertContains('mix:title', $meta->mixins);
    }

    public function testLoadMetadataClassInheritanceChildCanOverride(): void
    {
        $meta = $this->getMetadataFor(ClassInheritanceChildOverridesMappingObject::class);
        $this->assertTrue($meta->referenceable);
        $this->assertEquals('bar', $meta->translator);
        $this->assertEquals('nt:test-override', $meta->nodeType);
        $this->assertEquals(['mix:baz'], $meta->mixins);
        $this->assertEquals('full', $meta->versionable);
        $this->assertEquals(BarfooRepository::class, $meta->customRepositoryClassName);
    }

    public function testValidateUuidNotReferenceable(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('is not referenceable');
        $this->getMetadataFor(UuidMappingObjectNotReferenceable::class);
    }

    public function testValidateTranslatableNoStrategy(): void
    {
        $this->expectException(MappingException::class);
        $this->getMetadataFor(TranslatorMappingObjectNoStrategy::class);
    }

    public function testValidateChildClassesIfLeafConflict(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Cannot map a document as a leaf and define child classes for "Doctrine\Tests\ODM\PHPCR\Mapping\Model\ChildClassesAndLeafObject"');
        $this->getMetadataFor(ChildClassesAndLeafObject::class);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testValidateTranslatable(): void
    {
        $this->getMetadataFor(TranslatorMappingObject::class);
    }

    public function testLoadClassMetadataEvent(): void
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

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $this->called = true;
        $this->dm = $args->getObjectManager();
        $this->meta = $args->getClassMetadata();
    }
}
