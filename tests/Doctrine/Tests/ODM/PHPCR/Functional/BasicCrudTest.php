<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use PHPCR\PropertyType;
use PHPCR\Util\UUIDHelper;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @group functional
 */
class BasicCrudTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * Class name of the document class
     * @var string
     */
    private $type;

    /**
     * @var \PHPCR\NodeInterface
     */
    private $node;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\User';
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);

        $user = $this->node->addNode('user');
        $user->setProperty('username', 'lsmith');
        $user->setProperty('note', 'test');
        $user->setProperty('numbers', array(3, 1, 2));
        $user->setProperty('parameters', array('bar', 'dong'));
        $user->setProperty('parameterKey', array('foo', 'ding'));
        $user->setProperty('phpcr:class', $this->type, PropertyType::STRING);
        $this->dm->getPhpcrSession()->save();
    }

    public function testInsert()
    {
        $user = new User();
        $user->username = "test";
        $user->numbers = array(1, 2, 3);
        $user->id = '/functional/test';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find($this->type, '/functional/test');

        $this->assertNotNull($userNew, "Have to hydrate user object!");
        $this->assertEquals($user->username, $userNew->username);
        $this->assertEquals($user->numbers, $userNew->numbers);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testInsertTwice()
    {
        $user = new User();
        $user->username = "test";
        $user->numbers = array(1, 2, 3);
        $user->id = '/functional/test';
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = new User();
        $user->username = "toast";
        $user->numbers = array(1, 2, 3);
        $user->id = '/functional/test';
        $this->dm->persist($user);
    }

    public function testFindByClass()
    {
        $user = $this->node->addNode('userWithAlias');
        $user->setProperty('username', 'dbu');
        $user->setProperty('numbers', array(3, 1, 2));
        $user->setProperty('phpcr:class', $this->type, \PHPCR\PropertyType::STRING);
        $this->dm->getPhpcrSession()->save();

        $userWithAlias = $this->dm->find(null, '/functional/userWithAlias');

        $this->assertEquals('dbu', $userWithAlias->username);
    }

    public function testFindById()
    {
        $user = $this->dm->find(null, '/functional/bogus');
        $this->assertNull($user);

        $newUser = new User();
        $newUser->username = 'test';
        $newUser->id = '/functional/test';

        $this->dm->persist($newUser);
        $this->dm->flush();
        $this->dm->clear();

        $foundUser = $this->dm->find(null, '/functional/test');
        $this->assertNotNull($foundUser);
        $this->assertEquals($newUser->username, $foundUser->username);
    }

    public function testFindByUuid()
    {
        $generator = $this->dm->getConfiguration()->getUuidGenerator();

        $user = $this->dm->find(null, $generator());
        $this->assertNull($user);

        $newUser = new UserWithUuid();
        $newUser->username = 'test';
        $newUser->id = '/functional/test';

        $this->dm->persist($newUser);
        $this->dm->flush();
        $this->dm->clear();

        $foundUser = $this->dm->find(null, $newUser->uuid);
        $this->assertNotNull($foundUser);
        $this->assertEquals('test', $foundUser->username);
        $this->assertEquals('/functional/test', $foundUser->id);
    }

    public function testFindNonFlushed()
    {
        $user = new User();
        $user->username = "test";
        $user->numbers = array(1, 2, 3);
        $user->id = '/functional/test';

        $this->dm->persist($user);

        $userNew = $this->dm->find($this->type, '/functional/test');

        $this->assertNotNull($userNew, "Have to hydrate user object!");
        $this->assertEquals($user->username, $userNew->username);
    }

    public function testGetUuuidAfterPersist()
    {
        $newUser = new UserWithUuid();
        $newUser->username = 'test';
        $newUser->id = '/functional/test';

        $this->dm->persist($newUser);

        $uuidPostPersist = $newUser->uuid;
        $this->assertNotNull($uuidPostPersist);

        $this->dm->flush();
        $this->dm->clear();

        $flushedUser = $this->dm->find(null, '/functional/test');

        $this->assertEquals($uuidPostPersist, $flushedUser->uuid);
    }

    public function testExistingUuuid()
    {
        $testUuid = UuidHelper::generateUUID();

        $newUser = new UserWithUuid();
        $newUser->username = 'test';
        $newUser->id = '/functional/test';
        $newUser->uuid = $testUuid;

        $this->dm->persist($newUser);

        $uuidPostPersist = $newUser->uuid;
        $this->assertNotNull($uuidPostPersist);
        $this->assertEquals($testUuid, $uuidPostPersist);

        $this->dm->flush();
        $this->dm->clear();

        $flushedUser = $this->dm->find(null, '/functional/test');

        $this->assertEquals($testUuid, $flushedUser->uuid);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\RuntimeException
     */
    public function testBadUuidSetting()
    {
        $newUser = new UserWithUuid();
        $newUser->username = 'test';
        $newUser->id = '/functional/test';
        $newUser->uuid = 'bad-uuid';

        $this->dm->persist($newUser);
    }

    public function testInsertWithCustomIdStrategy()
    {
        $user = new User3();
        $user->username = "test3";

        $this->dm->persist($user);

        $this->assertEquals('/functional/test3', $user->id);

        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User3', '/functional/test3');

        $this->assertNotNull($userNew, "Have to hydrate user object!");
        $this->assertEquals($user->username, $userNew->username);
    }

    public function testMultivaluePropertyWithOnlyOneValueUpdatedToMultiValue()
    {
        $user = new User();
        $user->numbers = array(1);
        $user->id = '/functional/test';
        $user->username = 'testuser';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find($this->type, '/functional/test');
        $this->assertEquals($user->numbers, $userNew->numbers);

        $userNew->numbers = array(1, 2);
        $this->dm->flush();
        $this->dm->clear();

        $userNew2 = $this->dm->find($this->type, '/functional/test');
        $this->assertEquals($userNew->numbers, $userNew2->numbers);
    }

    public function testRemove()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);
        $this->dm->flush();

        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNull($user, 'User must be null after deletion');
    }

    public function testRemoveWithClear()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNull($user, 'User must be null after deletion');
    }

    public function testRemoveWithPersist()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);
        $this->dm->persist($user);
        $this->dm->flush();

        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');
    }

    public function testRemoveNoFlush()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');
    }

    public function testRemoveAndInsertAfterFlush()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);
        $this->dm->flush();

        $user = new User2();
        $user->username = "test";
        $user->id = '/functional/user';
        $this->dm->persist($user);

        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User2', '/functional/user');

        $this->assertNotNull($userNew, "Have to hydrate user object!");
        $this->assertEquals($user->username, $userNew->username);
    }

    public function testRemoveAndReinsert()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);

        $user->username = "test";
        $user->id = '/functional/user';
        $this->dm->persist($user);

        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find($this->type, '/functional/user');

        $this->assertNotNull($userNew, "Have to hydrate user object!");
        $this->assertEquals($user->username, $userNew->username);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testRemoveAndInsertBeforeFlush()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);

        $user = new User2();
        $user->username = "test";
        $user->id = '/functional/user';
        $this->dm->persist($user);
    }

    public function testUpdate1()
    {
        $user = new User();
        $user->username = "test";
        $user->numbers = array(1, 2, 3);
        $user->id = '/functional/user2';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find($this->type, '/functional/user2');
        $userNew->username = "test2";
        $userNew->numbers = array(4, 5, 6);
        $userNew->id = '/functional/user2';

        $this->dm->persist($userNew);
        $this->dm->flush();
        $this->dm->clear();

        $userNew2 = $this->dm->find($this->type, '/functional/user2');

        $this->assertNotSame($user, $userNew);
        $this->assertNotSame($userNew, $userNew2);
        $this->assertEquals($userNew->username, $userNew2->username);
        $this->assertEquals($userNew->numbers, $userNew2->numbers);

        $user = $this->dm->find($this->type, '/functional/user');
        $user->numbers = null;

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertEquals($user->numbers, array());

        $user->numbers = array();

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertEquals($user->numbers, array());
    }

    public function testUpdate2()
    {
        $user = $this->dm->find($this->type, '/functional/user');
        $user->username = "new-name";

        $this->dm->flush();
        $this->dm->clear();

        $newUser = $this->dm->find($this->type, '/functional/user');
        $this->assertEquals('new-name', $newUser->username);
    }

    public function testInsertUpdateMultiple()
    {
        $user1 = $this->dm->find($this->type, '/functional/user');
        $user1->username = "new-name";

        $user2 = new User();
        $user2->username = "test2";
        $user2->id = '/functional/user2222';

        $user3 = new User();
        $user3->username = "test3";
        $user3->id = '/functional/user3333';

        $this->dm->persist($user2);
        $this->dm->persist($user3);
        $this->dm->flush();
        $this->dm->clear();

        $pUser1 = $this->dm->find($this->type, '/functional/user');
        $pUser2 = $this->dm->find($this->type, '/functional/user2222');
        $pUser3 = $this->dm->find($this->type, '/functional/user3333');

        $this->assertEquals('/functional/user', $pUser1->id);
        $this->assertEquals('new-name', $pUser1->username);
        $this->assertEquals('/functional/user2222', $pUser2->id);
        $this->assertEquals('test2', $pUser2->username);
        $this->assertEquals('/functional/user3333', $pUser3->id);
        $this->assertEquals('test3', $pUser3->username);
    }

    public function testNullRemovesTheProperty()
    {
        $user1 = $this->dm->find($this->type, '/functional/user');
        $user1->note = null;
        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->find($this->type, '/functional/user');
        $this->assertFalse($user2->node->hasProperty('note'));
        $this->assertNull($user2->note);
    }


    public function testNoIdProperty()
    {
        $functional = $this->dm->find(null, '/functional');

        $user = new User5();
        $user->username = "test";
        $user->numbers = array(1, 2, 3);
        $user->nodename = 'test';
        $user->parent = $functional;

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User5', '/functional/test');

        $userNew->username = "test2";
        $this->dm->flush();

        $userNew = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User5', '/functional/test');

        $this->assertNotNull($userNew, "Have to hydrate user object!");
        $this->assertEquals('test2', $userNew->username);
        $this->assertEquals($user->numbers, $userNew->numbers);
    }

    public function testAutoId()
    {
        $functional = $this->dm->find(null, '/functional');

        $user = new User6();
        $user->username = "test";
        $user->numbers = array(1, 2, 3);
        $user->parent = $functional;

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User6', '/functional/'.$user->nodename);
        $this->assertEquals($userNew->nodename, $user->nodename);
    }

    public function testAssocProperty()
    {
        $user = new User();
        $user->username = "test";
        $assocArray = array('foo' => 'bar', 'ding' => 'dong', 'dong' => null, 'dang' => null);
        $user->parameters = $assocArray;
        $user->id = '/functional/test';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(null, '/functional/test');

        $this->assertNotNull($user);
        $this->assertEquals($user->parameters, $assocArray);

        $assocArray = array('foo' => 'bar', 'hello' => 'world', 'check' => 'out');
        $user->parameters = $assocArray;

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(null, '/functional/test');

        $this->assertNotNull($user);
        $this->assertEquals($user->parameters, $assocArray);

        unset($user->parameters['foo']);
        unset($assocArray['foo']);
        $user->parameters['boo'] = 'yah';
        $assocArray['boo'] = 'yah';
        $user->parameters['hello'] = 'welt';
        $assocArray['hello'] = 'welt';

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(null, '/functional/test');

        $this->assertNotNull($user);
        $this->assertEquals($user->parameters, $assocArray);
    }

    public function testAssocNumberProperty()
    {
        $user = new User();
        $user->username = "test";
        $assocArray = array('foo' => 1, 'ding' => 2);
        $user->assocNumbers = $assocArray;
        $user->id = '/functional/test';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(null, '/functional/test');

        $this->assertNotNull($user);
        $this->assertEquals($user->assocNumbers, $assocArray);
    }

    public function testVersionedDocument()
    {
        $user = new VersionTestObj();
        $user->username = "test";
        $user->numbers = array(1, 2, 3);
        $user->id = '/functional/test';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find(null, '/functional/test');
        $this->assertNotNull($userNew, "Have to hydrate user object!");
        $this->assertEquals($user->username, $userNew->username);
        $this->assertEquals($user->numbers, $userNew->numbers);
    }

    /**
     * Create a node with a bad name and explicitly persist it without
     * adding it to any parent's children collection.
     *
     * @expectedException \Doctrine\ODM\PHPCR\Id\IdException
     */
    public function testIllegalNodename()
    {
        $functional = $this->dm->find(null, '/functional');

        $user = new User5();
        $user->username = 'test';
        $user->nodename = 'bad/name';
        $user->parent = $functional;
        $this->dm->persist($user);

        $this->dm->flush();
    }

    /**
     * Retrieve an existing node and try to move it by assigning it
     * an illegal name.
     *
     * @expectedException \Doctrine\ODM\PHPCR\Id\IdException
     */
    public function testIllegalNodenameMove()
    {
        $functional = $this->dm->find(null, '/functional');

        $user = new User5();
        $user->username = 'test';
        $user->parent = $functional;
        $user->nodename = 'goodname';
        $this->dm->persist($user);
        $this->dm->flush();

        $user->nodename = 'bad/name';
        $this->dm->flush();
    }

    public function testChangeset()
    {
        $user = $this->node->getNode('user');
        // the property is not nullable, but this should only be checked on saving, not on loading
        $user->getProperty('username')->remove();
        $this->dm->getPhpcrSession()->save();

        $userDoc = $this->dm->find(null, $user->getPath());

        $this->assertInstanceOf($this->type, $userDoc);
        $this->assertNull($userDoc->username);

        // nothing should happen, we did not alter any value
        $this->dm->flush();
    }
}

/**
 * @PHPCRODM\Document()
 */
class User
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\String */
    public $username;
    /** @PHPCRODM\String(nullable=true) */
    public $note;
    /** @PHPCRODM\Int(multivalue=true,nullable=true) */
    public $numbers;
    /** @PHPCRODM\String(assoc="",nullable=true) */
    public $parameters;
    /** @PHPCRODM\Long(assoc="",nullable=true) */
    public $assocNumbers;
}

/**
 * @PHPCRODM\Document()
 */
class User2
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String */
    public $username;
}

/**
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\ODM\PHPCR\Functional\User3Repository")
 */
class User3
{
    /** @PHPCRODM\Id(strategy="repository") */
    public $id;
    /** @PHPCRODM\String */
    public $username;
}

/**
 * @PHPCRODM\Document()
 */
class User5
{
    /** @PHPCRODM\Nodename */
    public $nodename;
    /** @PHPCRODM\ParentDocument */
    public $parent;
    /** @PHPCRODM\String */
    public $username;
    /** @PHPCRODM\Int(multivalue=true,nullable=true) */
    public $numbers;
}

/**
 * @PHPCRODM\Document()
 */
class User6 extends User5
{
    /** @PHPCRODM\Id(strategy="auto") */
    public $id;
}

class User3Repository extends DocumentRepository implements RepositoryIdInterface
{
    /**
     * Generate a document id
     *
     * @param object $document
     * @return string
     */
    public function generateId($document, $parent = null)
    {
        return '/functional/'.$document->username;
    }
}

/**
 * @PHPCRODM\Document()
 */
class TeamUser extends User
{
    /** @PHPCRODM\ParentDocument */
    public $parent;
}

/**
 * @PHPCRODM\Document(versionable="full")
 */
class VersionTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;

    /** @PHPCRODM\VersionName */
    public $versionName;

    /** @PHPCRODM\VersionCreated */
    public $versionCreated;

    /** @PHPCRODM\String */
    public $username;

    /** @PHPCRODM\Int(multivalue=true,nullable=true) */
    public $numbers;
}

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class UserWithUuid extends User
{
    /** @PHPCRODM\Uuid */
    public $uuid;
}
