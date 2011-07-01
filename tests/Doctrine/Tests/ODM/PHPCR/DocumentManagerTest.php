<?php

namespace Doctrine\Tests\ODM\PHPCR;

/**
 * @group unit
 */
class DocumentManagerTest extends PHPCRTestCase
{
    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::create
     * @covers Doctrine\ODM\PHPCR\DocumentManager::getConfiguration
     */
    public function testNewInstanceFromConfiguration()
    {
        $session = $this->getMock('PHPCR\SessionInterface');
        $config = new \Doctrine\ODM\PHPCR\Configuration();

        $dm = \Doctrine\ODM\PHPCR\DocumentManager::create($session, $config);

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\DocumentManager', $dm);
        $this->assertSame($config, $dm->getConfiguration());
    }

    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::getMetadataFactory
     */
    public function testGetMetadataFactory()
    {
        $session = $this->getMock('PHPCR\SessionInterface');

        $dm = \Doctrine\ODM\PHPCR\DocumentManager::create($session);

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory', $dm->getMetadataFactory());
    }

    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::getClassMetadata
     */
    public function testGetClassMetadataFor()
    {
        $session = $this->getMock('PHPCR\SessionInterface');

        $dm = \Doctrine\ODM\PHPCR\DocumentManager::create($session);

        $cmf = $dm->getMetadataFactory();
        $cmf->setMetadataFor('stdClass', new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata('stdClass'));

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Mapping\ClassMetadata', $dm->getClassMetadata('stdClass'));
    }

    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::contains
     */
    public function testContains()
    {
        $session = $this->getMock('PHPCR\SessionInterface');

        $dm = \Doctrine\ODM\PHPCR\DocumentManager::create($session);

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
        $session = $this->getMock('PHPCR\SessionInterface');

        $dm = new DocumentManagerGetClassMetadata($session);

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\DocumentRepository', $dm->getRepository('foo'));
        $this->assertInstanceOf('stdClass', $dm->getRepository('foo2'));

        // call again to test the cache
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\DocumentRepository', $dm->getRepository('foo'));
    }
}

class DocumentManagerGetClassMetadata extends \Doctrine\ODM\PHPCR\DocumentManager
{
    private $callCount = 0;

    /**
     * @param  string $class
     * @return ClassMetadata
     */
    public function getClassMetadata($class)
    {
        ++$this->callCount;
        $metadata = new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata('stdClass');
        switch ($this->callCount) {
            case '1':
                break;
            case '2':
                $metadata->customRepositoryClassName = "stdClass";
                break;
            default:
                throw new \Exception('getClassMetadata called more than 2 times');
                break;
        }
        return $metadata;
    }
}
