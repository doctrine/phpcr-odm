<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\UnitOfWork;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Id\idGenerator;

class UnitOfWorkTest extends PHPCRTestCase
{
    private $dm;
    private $uow;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\ODM\PHPCR\UoWUser';
        $this->dm = \Doctrine\ODM\PHPCR\DocumentManager::create();
        $this->uow = new UnitOfWork($this->dm);

        $metadata = new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata($this->type);
        $metadata->mapProperty(array('fieldName' => 'id', 'id' => true));
        $metadata->mapProperty(array('fieldName' => 'username', 'type' => 'string'));

        $cmf = $this->dm->getMetadataFactory();
        $cmf->setMetadataFor($this->type, $metadata);
    }
/*
    public function testCreateDocument()
    {
        $user = $this->uow->createDocument($this->type, array('_id' => '1', '_rev' => 23, 'username' => 'foo'));

        $this->assertType($this->type, $user);
        $this->assertEquals('1', $user->id);
        $this->assertEquals('foo', $user->username);
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($user));
        $this->assertEquals(1, $this->uow->getDocumentIdentifier($user));
        $this->assertEquals(23, $this->uow->getDocumentRevision($user));

        $this->assertEquals(array('id' => '1', 'username' => 'foo'), $this->uow->getOriginalData($user));
    }

    public function testCreateDocument_UseIdentityMap()
    {
        $user1 = $this->uow->createDocument($this->type, array('_id' => '1', '_rev' => 1, 'username' => 'foo'));
        $user2 = $this->uow->createDocument($this->type, array('_id' => '1', '_rev' => 1, 'username' => 'foo'));

        $this->assertSame($user1, $user2);
    }

    public function testTryGetById()
    {
        $user1 = $this->uow->createDocument($this->type, array('_id' => '1', '_rev' => 1, 'username' => 'foo'));

        $user2 = $this->uow->tryGetById(1, $this->type);

        $this->assertSame($user1, $user2);
    }
*/
    public function testScheduleInsertion()
    {
        $object = new UoWUser();
        $object->id = "1";
        $object->username = "bar";

        $this->uow->scheduleInsert($object, '/user');
    }

    public function testScheduleInsertCancelsScheduleRemove()
    {
        $object = new UoWUser();
        $object->username = "bar";

        $this->uow->scheduleRemove($object);

        $this->assertEquals(UnitOfWork::STATE_REMOVED, $this->uow->getDocumentState($object));

        $this->uow->scheduleInsert($object, '/path');

        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($object));
    }
}

class UoWUser
{
    public $id;
    public $username;
}