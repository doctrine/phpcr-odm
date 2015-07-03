<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Configuration;
use Doctrine\ODM\PHPCR\UnitOfWork;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

use Jackalope\Factory;
use Jackalope\Node;

/**
 * TODO: remove Jackalope dependency
 *
 * @group unit
 */
class UnitOfWorkTest extends PHPCRTestCase
{
    /** @var DocumentManager */
    private $dm;
    /** @var UnitOfWork */
    private $uow;
    /** @var Factory */
    private $factory;
    /** @var \PHPCR\SessionInterface */
    private $session;
    /** @var \Jackalope\ObjectManager */
    private $objectManager;
    /** @var string */
    private $type;

    public function setUp()
    {
        if (!class_exists('Jackalope\Factory', true)) {
            $this->markTestSkipped('The Node needs to be properly mocked/stubbed. Remove dependency to Jackalope');
        }

        $this->factory = new Factory;
        $this->session = $this->getMock('Jackalope\Session', array(), array($this->factory), '', false);
        $this->objectManager = $this->getMock('Jackalope\ObjectManager', array(), array($this->factory), '', false);

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

    protected function createNode($id, $username)
    {
        $repository = $this->getMockBuilder('Jackalope\Repository')->disableOriginalConstructor()->getMock();
        $this->session->expects($this->any())
            ->method('getRepository')
            ->with()
            ->will($this->returnValue($repository));

        $nodeData = array(
            "jcr:primaryType" => "rep:root",
            "jcr:system" => array(),
            'username' => $username,
        );
        return new Node($this->factory, $nodeData, $id, $this->session, $this->objectManager);
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

        $user2 = $this->uow->getDocumentById('/somepath', $this->type);

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
}

class UoWUser
{
    public $id;
    public $username;
}
