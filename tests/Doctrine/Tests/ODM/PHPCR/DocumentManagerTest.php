<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Configuration;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\PHPCRException;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use PHPCR\ItemNotFoundException;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QueryManagerInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\UUIDHelper;
use PHPCR\WorkspaceInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group unit
 */
class DocumentManagerTest extends PHPCRTestCase
{
    /**
     * @covers \Doctrine\ODM\PHPCR\DocumentManager::find
     */
    public function testFind(): void
    {
        $fakeUuid = UUIDHelper::generateUUID();
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('getNodeByIdentifier')->willThrowException(new ItemNotFoundException(sprintf('403: %s', $fakeUuid)));
        $config = new Configuration();

        $dm = DocumentManager::create($session, $config);

        $nonExistent = $dm->find(null, $fakeUuid);

        $this->assertNull($nonExistent);
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\DocumentManager::findTranslation
     */
    public function testFindTranslation(): void
    {
        $fakeUuid = UUIDHelper::generateUUID();
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('getNodeByIdentifier')->willThrowException(new ItemNotFoundException(sprintf('403: %s', $fakeUuid)));
        $config = new Configuration();

        $dm = DocumentManager::create($session, $config);

        $nonExistent = $dm->findTranslation(null, $fakeUuid, 'en');

        $this->assertNull($nonExistent);
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\DocumentManager::create
     * @covers \Doctrine\ODM\PHPCR\DocumentManager::getConfiguration
     */
    public function testNewInstanceFromConfiguration(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $config = new Configuration();

        $dm = DocumentManager::create($session, $config);

        $this->assertInstanceOf(DocumentManager::class, $dm);
        $this->assertSame($config, $dm->getConfiguration());
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\DocumentManager::getMetadataFactory
     */
    public function testGetMetadataFactory(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $dm = DocumentManager::create($session);

        $this->assertInstanceOf(ClassMetadataFactory::class, $dm->getMetadataFactory());
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\DocumentManager::getClassMetadata
     */
    public function testGetClassMetadataFor(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $dm = DocumentManager::create($session);

        $cmf = $dm->getMetadataFactory();
        $cmf->setMetadataFor('stdClass', new ClassMetadata('stdClass'));

        $this->assertInstanceOf(ClassMetadata::class, $dm->getClassMetadata('stdClass'));
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\DocumentManager::contains
     */
    public function testContains(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $dm = DocumentManager::create($session);

        $obj = new \stdClass();
        $uow = $dm->getUnitOfWork();

        $method = new \ReflectionMethod($uow, 'registerDocument');
        $method->setAccessible(true);
        $method->invoke($uow, $obj, '/foo');
        $method->setAccessible(false);

        $this->assertTrue($dm->contains($obj));
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\DocumentManager::getRepository
     */
    public function testGetRepository(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $dm = new DocumentManagerGetClassMetadata($session);

        $this->assertInstanceOf(DocumentRepository::class, $dm->getRepository('foo'));
        $this->assertInstanceOf('stdClass', $dm->getRepository('foo2'));

        // call again to test the cache
        $this->assertInstanceOf(DocumentRepository::class, $dm->getRepository('foo'));
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\DocumentManager::escapeFullText
     */
    public function testEscapeFullText(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $dm = DocumentManager::create($session);

        $string = $dm->escapeFullText('Some{String}Wit"h[]Illegal^^^Chara\cte?rs:!');
        $this->assertEquals($string, 'Some\{String\}Wit"h\[\]Illegal\^\^\^Chara\cte\?rs\:\!');
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\DocumentManager::createQueryBuilder
     */
    public function testCreateQueryBuilder(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $workspace = $this->createMock(WorkspaceInterface::class);
        $queryManager = $this->createMock(QueryManagerInterface::class);
        $qomf = $this->createMock(QueryObjectModelFactoryInterface::class);

        $session->expects($this->once())
            ->method('getWorkspace')
            ->willReturn($workspace);
        $workspace->expects($this->once())
            ->method('getQueryManager')
            ->willReturn($queryManager);
        $queryManager->expects($this->once())
            ->method('getQOMFactory')
            ->willReturn($qomf);

        $dm = DocumentManager::create($session);
        $qb = $dm->createQueryBuilder();
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }

    public function testGetDocumentIdReturnsValueOfUnitOfWork()
    {
        $session = $this->createMock(SessionInterface::class);

        $dm = DocumentManager::create($session);

        $obj = new \stdClass();
        $uow = $dm->getUnitOfWork();

        $reflectionProperty = new \ReflectionProperty($uow, 'documentIds');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($uow, [spl_object_hash($obj) => '/foo']);
        $reflectionProperty->setAccessible(false);

        $this->assertEquals('/foo', $dm->getDocumentId($obj));
    }

    public function testGetDocumentIdForNonManagedDocumentsReturnsNull()
    {
        /** @var SessionInterface|MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $dm = DocumentManager::create($session);
        $obj = new \stdClass();
        $this->expectException(PHPCRException::class);
        $dm->getDocumentId($obj);
    }
}

class DocumentManagerGetClassMetadata extends DocumentManager
{
    private $callCount = 0;

    /**
     * @param string $class
     */
    public function getClassMetadata($class): ClassMetadata
    {
        ++$this->callCount;
        $metadata = new ClassMetadata('stdClass');
        switch ($this->callCount) {
            case '1':
                break;
            case '2':
                $metadata->customRepositoryClassName = 'stdClass';

                break;
            default:
                throw new \Exception('getClassMetadata called more than 2 times');
        }

        return $metadata;
    }
}
