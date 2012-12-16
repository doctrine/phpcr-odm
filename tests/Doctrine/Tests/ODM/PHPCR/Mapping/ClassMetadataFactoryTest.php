<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;

class ClassMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

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

    public function testLoadMetadata_referenceableChildOverriddenAsFalse()
    {
        // if the child class overrides referenceable as false it is not taken into account
        // as we only ever set the referenceable property to TRUE. This prevents us from
        // knowing if the user has explicitly set referenceable to FALSE on a child entity.
        $meta = $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceableChildReferenceableFalseMappingObject');

        $this->assertTrue($meta->referenceable);
    }

    public function testLoadMetadata_defaults()
    {
        $meta = $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\DefaultMappingObject');
        $this->assertFalse($meta->referenceable);
        $this->assertNull($meta->translator);
        $this->assertEquals('nt:unstructured', $meta->nodeType);
        $this->assertFalse($meta->versionable);
        $this->assertNull($meta->customRepositoryClassName);
    }

    public function testLoadMetadata_classInheritanceChild()
    {
        $meta = $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\ClassInheritanceChildMappingObject');
        $this->assertTrue($meta->referenceable);
        $this->assertEquals('foo', $meta->translator);
        $this->assertEquals('nt:test', $meta->nodeType);
        $this->assertEquals('simple', $meta->versionable);
        $this->assertEquals('Foobar', $meta->customRepositoryClassName);
    }

    public function testLoadMetadata_classInheritanceChildCanOverride()
    {
        $meta = $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\ClassInheritanceChildOverridesMappingObject');
        $this->assertTrue($meta->referenceable);
        $this->assertEquals('bar', $meta->translator);
        $this->assertEquals('nt:test-override', $meta->nodeType);
        $this->assertEquals('full', $meta->versionable);
        $this->assertEquals('Barfoo', $meta->customRepositoryClassName);
    }

    /**
     * @expectedException Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testValidateTranslatableNoStrategy()
    {
        $meta = $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\TranslatorMappingObjectNoStrategy');
    }

    public function testValidateTranslatable()
    {
        $meta = $this->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\TranslatorMappingObject');
    }
}
