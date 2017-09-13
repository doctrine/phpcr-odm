<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use PHPCR\SessionInterface;
use PHPCR\Transaction\UserTransactionInterface;
use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\WorkspaceInterface;

/**
 * @group unit
 */
class DocumentManagerTest extends PHPCRTestCase
{
    /**
     * @var SessionInterface
     */
    protected $session;

    public function setUp()
    {
        $this->session = $this->getMockBuilder('PHPCR\SessionInterface')->getMock();
    }

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
        $session = $this->getMockBuilder('PHPCR\SessionInterface')->getMock();
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
        $session = $this->getMockBuilder('PHPCR\SessionInterface')->getMock();

        $dm = DocumentManager::create($session);

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory', $dm->getMetadataFactory());
    }

    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::getClassMetadata
     */
    public function testGetClassMetadataFor()
    {
        $session = $this->getMockBuilder('PHPCR\SessionInterface')->getMock();

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
        $dm = DocumentManager::create($this->session);

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
        $session = $this->getMockBuilder('PHPCR\SessionInterface')->getMock();

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
        $session = $this->getMockBuilder('PHPCR\SessionInterface')->getMock();

        $dm = DocumentManager::create($session);

        $string = $dm->escapeFullText('Some{String}Wit"h[]Illegal^^^Chara\cte?rs:!');
        $this->assertEquals($string, 'Some\{String\}Wit"h\[\]Illegal\^\^\^Chara\cte\?rs\:\!');
    }

    /**
     * @covers Doctrine\ODM\PHPCR\DocumentManager::createQueryBuilder
     */
    public function testCreateQueryBuilder()
    {
        $session = $this->getMockBuilder('PHPCR\SessionInterface')->getMock();
        $workspace = $this->getMockBuilder('PHPCR\WorkspaceInterface')->getMock();
        $queryManager = $this->getMockBuilder('PHPCR\Query\QueryManagerInterface')->getMock();
        $qomf = $this->getMockBuilder('PHPCR\Query\QOM\QueryObjectModelFactoryInterface')->getMock();
        $baseQuery = $this->getMockBuilder('PHPCR\Query\QueryInterface')->getMock();

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
