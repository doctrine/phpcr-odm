<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Configuration;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\UnitOfWork;
use Jackalope\Factory;
use Jackalope\Node;
use Jackalope\NodeType\NodeType;
use Jackalope\NodeType\NodeTypeManager;
use Jackalope\ObjectManager;
use Jackalope\Repository;
use Jackalope\Session;
use Jackalope\Workspace;
use PHPCR\SessionInterface;

/**
 * TODO: remove Jackalope dependency
 *
 * @group unit
 */
class UnitOfWorkTest extends PHPCRTestCase
{
    /**
     * @var UnitOfWork
     */
    private $uow;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var string
     */
    private $type;

    public function setUp(): void
    {
        if (!class_exists(Factory::class, true)) {
            $this->markTestSkipped('The Node needs to be properly mocked/stubbed. Remove dependency to Jackalope');
        }

        $this->factory = new Factory();
        $this->session = $this->createMock(Session::class);

        $this->objectManager = $this->createMock(ObjectManager::class);

        $this->type = UoWUser::class;
        $dm = DocumentManager::create($this->session);
        $this->uow = new UnitOfWork($dm);

        $cmf = $dm->getMetadataFactory();
        $metadata = new ClassMetadata($this->type);
        $metadata->initializeReflection($cmf->getReflectionService());
        $metadata->mapId(['fieldName' => 'id', 'id' => true]);
        $metadata->idGenerator = ClassMetadata::GENERATOR_TYPE_ASSIGNED;
        $metadata->mapField(['fieldName' => 'username', 'type' => 'string']);
        $cmf->setMetadataFor($this->type, $metadata);
    }

    protected function createNode($id, $username, $primaryType = 'rep:root'): Node
    {
        $repository = $this->createMock(Repository::class);
        $this->session
            ->method('getRepository')
            ->with()
            ->willReturn($repository);

        $type = $this->createMock(NodeType::class);
        $type
            ->method('getName')
            ->with()
            ->willReturn($primaryType);

        $ntm = $this->createMock(NodeTypeManager::class);
        $ntm
            ->method('getNodeType')
            ->with()
            ->willReturn($type);

        $workspace = $this->createMock(Workspace::class);
        $workspace
            ->method('getNodeTypeManager')
            ->with()
            ->willReturn($ntm);

        $this->session
            ->method('getWorkspace')
            ->with()
            ->willReturn($workspace);

        $this->session
               ->method('nodeExists')
            ->with($id)
            ->willReturn(true);

        $nodeData = [
            'jcr:primaryType' => $primaryType,
            'jcr:system' => [],
            'username' => $username,
        ];
        $node = new Node($this->factory, $nodeData, $id, $this->session, $this->objectManager);

        $this->session
            ->method('getNode')
            ->with($id)
            ->willReturn($node);

        return $node;
    }

    public function testGetOrCreateDocument(): void
    {
        $user = $this->uow->getOrCreateDocument($this->type, $this->createNode('/somepath', 'foo'));
        $this->assertInstanceOf($this->type, $user);
        $this->assertEquals('foo', $user->username);

        $method = new \ReflectionMethod($this->uow, 'getDocumentState');
        $method->setAccessible(true);
        $state = $method->invoke($this->uow, $user);
        $method->setAccessible(false);

        $this->assertEquals(UnitOfWork::STATE_MANAGED, $state);
        $this->assertEquals('/somepath', $this->uow->getDocumentId($user));
    }

    public function testGetOrCreateDocumentUsingIdentityMap(): void
    {
        $user1 = $this->uow->getOrCreateDocument($this->type, $this->createNode('/somepath', 'foo'));
        $user2 = $this->uow->getOrCreateDocument($this->type, $this->createNode('/somepath', 'foo'));

        $this->assertSame($user1, $user2);
    }

    public function testGetDocumentById(): void
    {
        $user1 = $this->uow->getOrCreateDocument($this->type, $this->createNode('/somepath', 'foo'));

        $user2 = $this->uow->getDocumentById('/somepath', $this->type);

        $this->assertSame($user1, $user2);
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\UnitOfWork::scheduleInsert
     * @covers \Doctrine\ODM\PHPCR\UnitOfWork::doScheduleInsert
     *
     * @doesNotPerformAssertions
     */
    public function testScheduleInsertion(): void
    {
        $object = new UoWUser();
        $object->username = 'bar';
        $object->id = '/somepath';

        $this->uow->scheduleInsert($object);
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\UnitOfWork::scheduleRemove
     * @covers \Doctrine\ODM\PHPCR\UnitOfWork::scheduleInsert
     * @covers \Doctrine\ODM\PHPCR\UnitOfWork::doScheduleInsert
     */
    public function testScheduleInsertCancelsScheduleRemove(): void
    {
        $object = new UoWUser();
        $object->username = 'bar';
        $object->id = '/somepath';

        $this->uow->scheduleInsert($object);

        $this->uow->scheduleRemove($object);

        $method = new \ReflectionMethod($this->uow, 'getDocumentState');
        $method->setAccessible(true);
        $state = $method->invoke($this->uow, $object);
        $method->setAccessible(false);

        $this->assertEquals(UnitOfWork::STATE_REMOVED, $state);

        $this->uow->scheduleInsert($object);

        $method->setAccessible(true);
        $state = $method->invoke($this->uow, $object);
        $method->setAccessible(false);

        $this->assertEquals(UnitOfWork::STATE_MANAGED, $state);
    }

    public function testUuid(): void
    {
        $class = new \ReflectionClass(UnitOfWork::class);
        $method = $class->getMethod('generateUuid');
        $method->setAccessible(true);

        $this->assertIsString($method->invoke($this->uow));

        $config = new Configuration();
        $config->setUuidGenerator(function () {
            return 'like-a-uuid';
        });
        $dm = DocumentManager::create($this->session, $config);
        $uow = new UnitOfWork($dm);
        $this->assertEquals('like-a-uuid', $method->invoke($uow));
    }

    /**
     * @author Rob Graham
     *
     * Test the registering of a version of a document, state should be set to STATE_FROZEN
     */
    public function testRegisterDocumentForVersion(): void
    {
        // create a node of type frozenNode (which is a version)
        $node = $this->createNode('/version/doc', 'foo', 'nt:frozenNode');
        $document = $this->uow->getOrCreateDocument($this->type, $node);
        $this->uow->registerDocument($document, '/version/doc');
        $this->assertEquals(UnitOfWork::STATE_FROZEN, $this->uow->getDocumentState($document), 'A version of a document is frozen as expected');
    }

    /**
     * @see https://github.com/doctrine/phpcr-odm/issues/637
     * @covers \Doctrine\ODM\PHPCR\UnitOfWork::computeSingleDocumentChangeSet
     *
     * @doesNotPerformAssertions
     */
    public function testComputeSingleDocumentChangeSetForRemovedDocument(): void
    {
        $object = new UoWUser();
        $object->username = 'bar';
        $object->id = '/somepath';

        $this->uow->scheduleRemove($object);

        // Should not throw "InvalidArgumentException: Document has to be managed for single computation"
        $this->uow->computeSingleDocumentChangeSet($object);
    }
}

class UoWUser
{
    public $id;

    public $username;
}
