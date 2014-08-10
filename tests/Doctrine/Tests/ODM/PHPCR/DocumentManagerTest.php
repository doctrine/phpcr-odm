<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

/**
 * @group unit
 */
class DocumentManagerTest extends PHPCRTestCase
{
    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::find
     */
    public function testFind()
    {
        $fakeUuid = \PHPCR\Util\UUIDHelper::generateUUID();
        $session = $this->getMockForAbstractClass('PHPCR\SessionInterface', array('getNodeByIdentifier'));
        $session->expects($this->once())->method('getNodeByIdentifier')->will($this->throwException(new \PHPCR\ItemNotFoundException(sprintf('403: %s', $fakeUuid))));
        $config = new \Doctrine\ODM\PHPCR\Configuration();

        $dm = DocumentManager::create($session, $config);

        $nonExistent = $dm->find(null, $fakeUuid);

        $this->assertNull($nonExistent);
    }

    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::findTranslation
     */
    public function testFindTranslation()
    {
        $fakeUuid = \PHPCR\Util\UUIDHelper::generateUUID();
        $session = $this->getMockForAbstractClass('PHPCR\SessionInterface', array('getNodeByIdentifier'));
        $session->expects($this->once())->method('getNodeByIdentifier')->will($this->throwException(new \PHPCR\ItemNotFoundException(sprintf('403: %s', $fakeUuid))));
        $config = new \Doctrine\ODM\PHPCR\Configuration();

        $dm = DocumentManager::create($session, $config);

        $nonExistent = $dm->findTranslation(null, $fakeUuid, 'en');

        $this->assertNull($nonExistent);
    }
    
    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::create
     * @covers Doctrine\ODM\PHPCR\DocumentManager::getConfiguration
     */
    public function testNewInstanceFromConfiguration()
    {
        $session = $this->getMock('PHPCR\SessionInterface');
        $config = new \Doctrine\ODM\PHPCR\Configuration();

        $dm = DocumentManager::create($session, $config);

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\DocumentManager', $dm);
        $this->assertSame($config, $dm->getConfiguration());
    }

    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::getMetadataFactory
     */
    public function testGetMetadataFactory()
    {
        $session = $this->getMock('PHPCR\SessionInterface');

        $dm = DocumentManager::create($session);

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory', $dm->getMetadataFactory());
    }

    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::getClassMetadata
     */
    public function testGetClassMetadataFor()
    {
        $session = $this->getMock('PHPCR\SessionInterface');

        $dm = DocumentManager::create($session);

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

        $dm = DocumentManager::create($session);

        $obj = new \stdClass;
        $uow = $dm->getUnitOfWork();

        $method = new \ReflectionMethod($uow, 'registerDocument');
        $method->setAccessible(true);
        $method->invoke($uow, $obj, '/foo');
        $method->setAccessible(false);

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

    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::escapeFullText
     */
    public function testEscapeFullText()
    {
        $session = $this->getMock('PHPCR\SessionInterface');

        $dm = DocumentManager::create($session);

        $string = $dm->escapeFullText('Some{String}Wit"h[]Illegal^^^Chara\cte?rs:!');
        $this->assertEquals($string, 'Some\{String\}Wit"h\[\]Illegal\^\^\^Chara\cte\?rs\:\!');
    }

    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::createQueryBuilder
     */
    public function testCreateQueryBuilder()
    {
        $session = $this->getMock('PHPCR\SessionInterface');
        $workspace = $this->getMock('PHPCR\WorkspaceInterface');
        $queryManager = $this->getMock('PHPCR\Query\QueryManagerInterface');
        $qomf = $this->getMock('PHPCR\Query\QOM\QueryObjectModelFactoryInterface');
        $baseQuery = $this->getMock('PHPCR\Query\QueryInterface');

        $session->expects($this->once())
            ->method('getWorkspace')
            ->will($this->returnValue($workspace));
        $workspace->expects($this->once())
            ->method('getQueryManager')
            ->will($this->returnValue($queryManager));
        $queryManager->expects($this->once())
            ->method('getQOMFactory')
            ->will($this->returnValue($qomf));


        $dm = DocumentManager::create($session);
        $qb = $dm->createQueryBuilder();
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder', $qb);
    }
}

class DocumentManagerGetClassMetadata extends DocumentManager
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
