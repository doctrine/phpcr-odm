<?php

namespace Doctrine\Tests\ODM\PHPCR;

class DocumentManagerTest extends PHPCRTestCase
{
    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::create
     * @covers Doctrine\ODM\PHPCR\DocumentManager::getConfiguration
     */
    public function testNewInstanceFromConfiguration()
    {
        $config = new \Doctrine\ODM\PHPCR\Configuration();

        $dm = \Doctrine\ODM\PHPCR\DocumentManager::create($config);

        $this->assertType('Doctrine\ODM\PHPCR\DocumentManager', $dm);
        $this->assertSame($config, $dm->getConfiguration());
    }

    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::getMetadataFactory
     */
    public function testGetMetadataFactory()
    {
        $dm = \Doctrine\ODM\PHPCR\DocumentManager::create();

        $this->assertType('Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory', $dm->getMetadataFactory());
    }

    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::getClassMetadata
     */
    public function testGetClassMetadataFor()
    {
        $dm = \Doctrine\ODM\PHPCR\DocumentManager::create();

        $cmf = $dm->getMetadataFactory();
        $cmf->setMetadataFor('stdClass', new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata('stdClass'));

        $this->assertType('Doctrine\ODM\PHPCR\Mapping\ClassMetadata', $dm->getClassMetadata('stdClass'));
    }

    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::contains
     */
    public function testContains()
    {
        $dm = \Doctrine\ODM\PHPCR\DocumentManager::create();

        $obj = new \stdClass;
        $uow = $dm->getUnitOfWork();
        $uow->registerManaged($obj, '/foo', '');

        $this->assertTrue($dm->contains($obj));
    }

    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::getRepository
     */
    public function testGetRepository()
    {
        $dm = $this->getMock('Doctrine\ODM\PHPCR\DocumentManager', array('getClassMetadata'));

        $metadata = new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata('stdClass');
        $metadata2 = new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata('stdClass');
        $metadata2->customRepositoryClassName = "stdClass";

        $dm->expects($this->exactly(2))
            ->method('getClassMetadata')
            ->will($this->onConsecutiveCalls($metadata, $metadata2));

        $this->assertType('Doctrine\ODM\PHPCR\DocumentRepository', $dm->getRepository('foo'));
        $this->assertType('stdClass', $dm->getRepository('foo2'));

        // call again to test the cache
        $this->assertType('Doctrine\ODM\PHPCR\DocumentRepository', $dm->getRepository('foo'));
    }
}