<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Configuration;
use Doctrine\ODM\PHPCR\UnitOfWork;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Jackalope\Factory;
use Jackalope\Node;
use PHPCR\Util\UUIDHelper;

/**
 * TODO: remove Jackalope dependency
 *
 * @group unit
 */
class UnitOfWorkTest extends PHPCRTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var UnitOfWork
     */
    private $uow;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var \PHPCR\SessionInterface
     */
    private $session;

    /**
     * @var \Jackalope\ObjectManager
     */
    private $objectManager;

    /**
     * @var string
     */
    private $type;

    public function setUp()
    {
        if (!class_exists('Jackalope\Factory', true)) {
            $this->markTestSkipped('The Node needs to be properly mocked/stubbed. Remove dependency to Jackalope');
        }

        $this->factory = new Factory;
        $this->session = $this->getMockBuilder('Jackalope\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = $this->getMockBuilder('Jackalope\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = 'Doctrine\Tests\ODM\PHPCR\UoWUser';
        $this->dm = DocumentManager::create($this->session);
        $this->uow = new UnitOfWork($this->dm);

        $cmf = $this->dm->getMetadataFactory();
        $metadata = new ClassMetadata($this->type);
        $metadata->initializeReflection($cmf->getReflectionService());
        $metadata->mapId(array('fieldName' => 'id', 'id' => true));
        $metadata->idGenerator = ClassMetadata::GENERATOR_TYPE_ASSIGNED;
        $metadata->mapField(array('fieldName' => 'username', 'type' => 'string'));
        $cmf->setMetadataFor($this->type, $metadata);
    }

    protected function createNode($id, $username, $primaryType = 'rep:root', $uuid = null)
    {
        $repository = $this->getMockBuilder('Jackalope\Repository')->disableOriginalConstructor()->getMock();
        $this->session->expects($this->any())
            ->method('getRepository')
            ->with()
            ->will($this->returnValue($repository));

        $type = $this->getMockBuilder('Jackalope\NodeType\NodeType')->disableOriginalConstructor()->getMock();
        $type->expects($this->any())
            ->method('getName')
            ->with()
            ->will($this->returnValue($primaryType));

        $ntm = $this->getMockBuilder('Jackalope\NodeType\NodeTypeManager')->disableOriginalConstructor()->getMock();
        $ntm->expects($this->any())
            ->method('getNodeType')
            ->with()
            ->will($this->returnValue($type));

        $workspace = $this->getMockBuilder('Jackalope\Workspace')->disableOriginalConstructor()->getMock();
        $workspace->expects($this->any())
            ->method('getNodeTypeManager')
            ->with()
            ->will($this->returnValue($ntm));

        $this->session->expects($this->any())
            ->method('getWorkspace')
            ->with()
            ->will($this->returnValue($workspace));

        $this->session->expects($this->any())
               ->method('nodeExists')
            ->with($id)
            ->will($this->returnValue(true));

        $nodeData = array(
            "jcr:primaryType" => $primaryType,
            "jcr:system" => array(),
            'username' => $username,
        );

        if (null !== $uuid) {
            $nodeData['jcr:uuid'] = $uuid;
        }

        $node = new Node($this->factory, $nodeData, $id, $this->session, $this->objectManager);

        $this->session->expects($this->any())
            ->method('getNode')
            ->with($id)
            ->will($this->returnValue($node));

        if (null !== $uuid) {
            $this->session->expects($this->any())
                ->method('getNodeByIdentifier')
                ->with($uuid)
                ->will($this->returnValue($node));
        }

        return $node;
    }

    public function testGetOrCreateDocument()
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

    public function testGetOrCreateDocumentUsingIdentityMap()
    {
        $user1 = $this->uow->getOrCreateDocument($this->type, $this->createNode('/somepath', 'foo'));
        $user2 = $this->uow->getOrCreateDocument($this->type, $this->createNode('/somepath', 'foo'));

        $this->assertSame($user1, $user2);
    }

    public function testGetDocumentById()
    {
        $user1 = $this->uow->getOrCreateDocument($this->type, $this->createNode('/somepath', 'foo'));

        $user2 = $this->uow->getDocumentByPathOrUuid('/somepath', $this->type);

        $this->assertSame($user1, $user2);
    }

    /**
     * @covers Doctrine\ODM\PHPCR\UnitOfWork::scheduleInsert
     * @covers Doctrine\ODM\PHPCR\UnitOfWork::doScheduleInsert
     */
    public function testScheduleInsertion()
    {
        $object = new UoWUser();
        $object->username = "bar";
        $object->id = '/somepath';

        $this->uow->scheduleInsert($object);
    }

    /**
     * @covers Doctrine\ODM\PHPCR\UnitOfWork::scheduleRemove
     * @covers Doctrine\ODM\PHPCR\UnitOfWork::scheduleInsert
     * @covers Doctrine\ODM\PHPCR\UnitOfWork::doScheduleInsert
     */
    public function testScheduleInsertCancelsScheduleRemove()
    {
        $object = new UoWUser();
        $object->username = "bar";
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

    public function testUuid()
    {
        $class = new \ReflectionClass('Doctrine\ODM\PHPCR\UnitOfWork');
        $method = $class->getMethod('generateUuid');
        $method->setAccessible(true);

        $this->assertInternalType('string', $method->invoke($this->uow));

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
    public function testRegisterDocumentForVersion()
    {
        // create a node of type frozenNode (which is a version)
        $node = $this->createNode('/version/doc', 'foo', 'nt:frozenNode');
        $document = $this->uow->getOrCreateDocument($this->type, $node);
        $this->uow->registerDocument($document, '/version/doc');
        $this->assertEquals(UnitOfWork::STATE_FROZEN, $this->uow->getDocumentState($document), 'A version of a document is frozen as expected');
    }

    /**
     * @see https://github.com/doctrine/phpcr-odm/issues/637
     * @covers Doctrine\ODM\PHPCR\UnitOfWork::computeSingleDocumentChangeSet
     */
    public function testComputeSingleDocumentChangeSetForRemovedDocument()
    {
        $object = new UoWUser();
        $object->username = "bar";
        $object->id = '/somepath';

        $this->uow->scheduleRemove($object);

        // Should not throw "InvalidArgumentException: Document has to be managed for single computation"
        $this->uow->computeSingleDocumentChangeSet($object);
    }

    public function testGetOrCreateProxy()
    {
        $user = $this->uow->getOrCreateDocument($this->type, $this->createNode('/somepath', 'foo'));
        $this->uow->clear();
        $userAsReference = $this->uow->getOrCreateProxy($user->id, get_class($user));

        $this->assertEquals(2, $this->uow->getDocumentState($userAsReference));
        $this->assertEquals($userAsReference, $this->uow->getDocumentByPathOrUuid($userAsReference->id));
    }

    public function testGetOrCreateProxyWithUuid()
    {
        $uuid = UUIDHelper::generateUUID();
        $user = $this->uow->getOrCreateDocument($this->type, $this->createNode('/somepath', 'foo', 'rep:root', $uuid));
        $this->uow->clear();
        $userAsReference = $this->uow->getOrCreateProxy($uuid, get_class($user));

        $this->assertEquals(2, $this->uow->getDocumentState($userAsReference));
        $this->assertEquals($userAsReference, $this->uow->getDocumentByPathOrUuid($uuid));
    }
}

class UoWUser
{
    public $id;
    public $username;
}
