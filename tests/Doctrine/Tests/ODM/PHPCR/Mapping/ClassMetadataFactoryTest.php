<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\Event;

class ClassMetadataFactoryTest extends \PHPUnit_Framework_TestCase
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
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $reader = new \Doctrine\Common\Annotations\AnnotationReader($cache);
        $annotationDriver = new \Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver($reader);
        $annotationDriver->addPaths(array(__DIR__ . '/Model'));
        $this->dm->getConfiguration()->setMetadataDriverImpl($annotationDriver);

        $cmf = new ClassMetadataFactory($this->dm);
        $meta = $cmf->getMetadataFor($fqn);
        return $meta;
    }

    public function setUp()
    {
        $session = $this->getMock('PHPCR\SessionInterface');
        $this->dm = \Doctrine\ODM\PHPCR\DocumentManager::create($session);
    }

    public function testNotMappedThrowsException()
    {
        $cmf = new ClassMetadataFactory($this->dm);

        $this->setExpectedException('Doctrine\ODM\PHPCR\Mapping\MappingException');
        $cmf->getMetadataFor('unknown');
    }

    public function testGetMapping()
    {
        $cmf = new ClassMetadataFactory($this->dm);

        $cm = new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata('stdClass');

        $cmf->setMetadataFor('stdClass', $cm);

        $this->assertTrue($cmf->hasMetadataFor('stdClass'));
        $this->assertSame($cm, $cmf->getMetadataFor('stdClass'));
    }

    public function testGetAllMetadata()
    {
        $driver = new \Doctrine\Common\Persistence\Mapping\Driver\PHPDriver(array(__DIR__ . '/Model/php'));
        $this->dm->getConfiguration()->setMetadataDriverImpl($driver);

        $cmf = new ClassMetadataFactory($this->dm);

        $cm = new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata('stdClass');
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
     */
    public function testValidateTranslatableNoStrategy()
    {
        $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\TranslatorMappingObjectNoStrategy');
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
