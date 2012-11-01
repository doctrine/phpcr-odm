<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;

class ClassMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

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
        
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $reader = new \Doctrine\Common\Annotations\AnnotationReader($cache);
        $annotationDriver = new \Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver($reader);
        $annotationDriver->addPaths(array(__DIR__ . '/Model'));
        $this->dm->getConfiguration()->setMetadataDriverImpl($annotationDriver);

        $cmf = new ClassMetadataFactory($this->dm);
        $meta = $cmf->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceableChildReferenceableFalseMappingObject');

        $this->assertTrue($meta->referenceable);
    }

    public function testLoadMetadata_referenceableChild()
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $reader = new \Doctrine\Common\Annotations\AnnotationReader($cache);
        $annotationDriver = new \Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver($reader);
        $annotationDriver->addPaths(array(__DIR__ . '/Model'));
        $this->dm->getConfiguration()->setMetadataDriverImpl($annotationDriver);

        $cmf = new ClassMetadataFactory($this->dm);
        $meta = $cmf->getMetadataFor('Doctrine\Tests\ODM\PHPCR\Mapping\Model\ReferenceableChildMappingObject');

        $this->assertTrue($meta->referenceable);
    }
}
