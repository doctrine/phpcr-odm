<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\Event;
use PHPUnit\Framework\TestCase;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use PHPCR\SessionInterface;

class ClassMetadataFactoryTest extends TestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * @param $fqn
     *
     * @return ClassMetadata
     */
    protected function getMetadataFor($fqn)
    {
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $annotationDriver = new \Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver($reader);
        $annotationDriver->addPaths(array(__DIR__ . '/Model'));
        $this->dm->getConfiguration()->setMetadataDriverImpl($annotationDriver);

        $cmf = new ClassMetadataFactory($this->dm);
        $meta = $cmf->getMetadataFor($fqn);

        return $meta;
    }

    public function setUp()
    {
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $this->dm = \Doctrine\ODM\PHPCR\DocumentManager::create($session);
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
        $driver = new PHPDriver(array(__DIR__ . '/Model/php'));
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

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testLoadMetadataReferenceableChildOverriddenAsFalse()
    {
        $meta = $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceableChildReferenceableFalseMappingObject');
    }

    public function testLoadMetadataDefaults()
    {
        $meta = $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\DefaultMappingObject');
        $this->assertFalse($meta->referenceable);
        $this->assertNull($meta->translator);
        $this->assertEquals('nt:unstructured', $meta->nodeType);
        $this->assertFalse($meta->versionable);
        $this->assertNull($meta->customRepositoryClassName);
    }

    public function testLoadMetadataClassInheritanceChild()
    {
        $meta = $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\ClassInheritanceChildMappingObject');
        $this->assertTrue($meta->referenceable);
        $this->assertEquals('foo', $meta->translator);
        $this->assertEquals('nt:test', $meta->nodeType);
        $this->assertEquals(array('mix:foo', 'mix:bar'), $meta->mixins);
        $this->assertEquals('simple', $meta->versionable);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\DocumentRepository', $meta->customRepositoryClassName);
    }

    public function testLoadInheritedMixins()
    {
        $meta = $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\InheritedMixinMappingObject');
        $this->assertCount(2, $meta->mixins);
        $this->assertContains('mix:lastModified', $meta->mixins);
        $this->assertContains('mix:title', $meta->mixins);
    }

    public function testLoadMetadataClassInheritanceChildCanOverride()
    {
        $meta = $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\ClassInheritanceChildOverridesMappingObject');
        $this->assertTrue($meta->referenceable);
        $this->assertEquals('bar', $meta->translator);
        $this->assertEquals('nt:test-override', $meta->nodeType);
        $this->assertEquals(array('mix:baz'), $meta->mixins);
        $this->assertEquals('full', $meta->versionable);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Model\BarfooRepository', $meta->customRepositoryClassName);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Mapping\MappingException
     * @expectedExceptionMessage is not referenceable
     */
    public function testValidateUuidNotReferenceable()
    {
        $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\UuidMappingObjectNotReferenceable');
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testValidateTranslatableNoStrategy()
    {
        $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\TranslatorMappingObjectNoStrategy');
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Mapping\MappingException
     * @expectedExceptionMessage Cannot map a document as a leaf and define child classes for "Doctrine\Tests\ODM\PHPCR\Mapping\Model\ChildClassesAndLeafObject"
     */
    public function testValidateChildClassesIfLeafConflict()
    {
        $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\ChildClassesAndLeafObject');
    }

    public function testValidateTranslatable()
    {
        $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\TranslatorMappingObject');
    }

    public function testLoadClassMetadataEvent()
    {
        $listener = new Listener;
        $evm = $this->dm->getEventManager();
        $evm->addEventListener(array(Event::loadClassMetadata), $listener);

        $meta = $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\DefaultMappingObject');
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
