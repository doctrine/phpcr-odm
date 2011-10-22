<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\UnitOfWork;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Id\idGenerator;

use Jackalope\Factory;
use Jackalope\Node;

/**
 * @group unit
 */
class UnitOfWorkTest extends PHPCRTestCase
{
    private $dm;
    private $uow;

    public function setUp()
    {
        $this->factory = new Factory; // TODO: remove jackalope dependencies
        $this->session = $this->getMock('Jackalope\Session', array(), array($this->factory), '', false);
        // TODO: remove jackalope dependency:
        $this->objectManager = $this->getMock('Jackalope\ObjectManager', array(), array($this->factory), '', false);

        $this->type = 'Doctrine\Tests\ODM\PHPCR\UoWUser';
        $this->dm = DocumentManager::create($this->session);
        $this->uow = new UnitOfWork($this->dm);

        $metadata = new ClassMetadata($this->type);
        $metadata->mapField(array('fieldName' => 'id', 'id' => true));
        $metadata->mapField(array('fieldName' => 'username', 'type' => 'string'));

        $cmf = $this->dm->getMetadataFactory();
        $cmf->setMetadataFor($this->type, $metadata);
    }

    protected function createNode($id, $username)
    {
        $this->markTestIncomplete('The Node needs to be properly mocked/stubbed. Remove dependeny to Jackalope');

        $nodeData = array(
            'jcr:primaryType' => "Name",
            "jcr:primaryType" => "rep:root",
            "jcr:system" => array(),
            'username' => $username,
        );
        return new Node($this->factory, $nodeData, $id, $this->session, $this->objectManager);
    }

    public function testCreateDocument()
    {
        $user = $this->uow->createDocument($this->type, $this->createNode('/somepath', 'foo'));
        $this->assertInstanceOf($this->type, $user);
        $this->assertEquals('foo', $user->username);
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($user));
        $this->assertEquals('/somepath', $this->uow->getDocumentId($user));

        $this->assertEquals(array('id' => '/somepath', 'username' => 'foo'), $this->uow->getOriginalData($user));
    }

    public function testCreateDocumentUsingIdentityMap()
    {
        $user1 = $this->uow->createDocument($this->type, $this->createNode('/somepath', 'foo'));
        $user2 = $this->uow->createDocument($this->type, $this->createNode('/somepath', 'foo'));

        $this->assertSame($user1, $user2);
    }

    public function testTryGetById()
    {
        $user1 = $this->uow->createDocument($this->type, $this->createNode('/somepath', 'foo'));

        $user2 = $this->uow->tryGetById('/somepath', $this->type);

        $this->assertSame($user1, $user2);
    }

    /**
     * @covers Doctrine\ODM\PHPCR\UnitOfWork::scheduleInsert
     * @covers Doctrine\ODM\PHPCR\UnitOfWork::doScheduleInsert
     */
    public function testScheduleInsertion()
    {
        $parent = $this->getMockBuilder('Jackalope\Node')->disableOriginalConstructor()->getMock();
        $parent->expects($this->once())
                      ->method('addNode')
                      ->with('somepath', null);
        $this->session->expects($this->once())
                      ->method('getNode')
                      ->with('/')
                      ->will($this->returnValue($parent));

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

        $this->uow->scheduleRemove($object);

        $this->assertEquals(UnitOfWork::STATE_REMOVED, $this->uow->getDocumentState($object));

        $this->uow->scheduleInsert($object);

        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($object));
    }
}

class UoWUser
{
    public $id;
    public $username;
}
