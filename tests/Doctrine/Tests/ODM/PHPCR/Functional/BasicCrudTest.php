<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Exception\RuntimeException;
use Doctrine\ODM\PHPCR\Id\IdException;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\Util\NodeHelper;
use PHPCR\Util\UUIDHelper;

/**
 * @group functional
 */
class BasicCrudTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * Class name of the document class
     *
     * @var string
     */
    private $type;

    /**
     * @var NodeInterface
     */
    private $node;

    public function setUp(): void
    {
        $this->type = User::class;
        $this->dm = $this->createDocumentManager([__DIR__]);
        $this->node = $this->resetFunctionalNode($this->dm);

        $user = $this->node->addNode('user');
        $user->setProperty('username', 'lsmith');
        $user->setProperty('note', 'test');
        $user->setProperty('numbers', [3, 1, 2]);
        $user->setProperty('parameters', ['bar', 'dong']);
        $user->setProperty('parameterKey', ['foo', 'ding']);
        $user->setProperty('phpcr:class', $this->type, PropertyType::STRING);
        $this->dm->getPhpcrSession()->save();
    }

    public function testInsert(): void
    {
        $user = new User();
        $user->username = 'test';
        $user->numbers = [1, 2, 3];
        $user->id = '/functional/test';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find($this->type, '/functional/test');

        $this->assertNotNull($userNew, 'Have to hydrate user object!');
        $this->assertEquals($user->username, $userNew->username);
        $this->assertEquals($user->numbers, $userNew->numbers);
    }

    public function testInsertTwice(): void
    {
        $user = new User();
        $user->username = 'test';
        $user->numbers = [1, 2, 3];
        $user->id = '/functional/test';
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = new User();
        $user->username = 'toast';
        $user->numbers = [1, 2, 3];
        $user->id = '/functional/test';

        $this->expectException(InvalidArgumentException::class);
        $this->dm->persist($user);
    }

    public function testFindByClass(): void
    {
        $user = $this->node->addNode('userWithAlias');
        $user->setProperty('username', 'dbu');
        $user->setProperty('numbers', [3, 1, 2]);
        $user->setProperty('phpcr:class', $this->type, PropertyType::STRING);
        $this->dm->getPhpcrSession()->save();

        $userWithAlias = $this->dm->find(null, '/functional/userWithAlias');

        $this->assertEquals('dbu', $userWithAlias->username);
    }

    public function testFindById(): void
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

    public function testFindByUuid(): void
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

    public function testFindNonFlushed(): void
    {
        $user = new User();
        $user->username = 'test';
        $user->numbers = [1, 2, 3];
        $user->id = '/functional/test';

        $this->dm->persist($user);

        $userNew = $this->dm->find($this->type, '/functional/test');

        $this->assertNotNull($userNew, 'Have to hydrate user object!');
        $this->assertEquals($user->username, $userNew->username);
    }

    public function testGetUuuidAfterPersist(): void
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

    public function testExistingUuuid(): void
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

    public function testBadUuidSetting(): void
    {
        $newUser = new UserWithUuid();
        $newUser->username = 'test';
        $newUser->id = '/functional/test';
        $newUser->uuid = 'bad-uuid';

        $this->expectException(RuntimeException::class);
        $this->dm->persist($newUser);
    }

    public function testInsertWithCustomIdStrategy(): void
    {
        $user = new User3();
        $user->username = 'test3';

        $this->dm->persist($user);

        $this->assertEquals('/functional/test3', $user->id);

        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find(User3::class, '/functional/test3');

        $this->assertNotNull($userNew, 'Have to hydrate user object!');
        $this->assertEquals($user->username, $userNew->username);
    }

    public function testMultivaluePropertyWithOnlyOneValueUpdatedToMultiValue(): void
    {
        $user = new User();
        $user->numbers = [1];
        $user->id = '/functional/test';
        $user->username = 'testuser';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find($this->type, '/functional/test');
        $this->assertEquals($user->numbers, $userNew->numbers);

        $userNew->numbers = [1, 2];
        $this->dm->flush();
        $this->dm->clear();

        $userNew2 = $this->dm->find($this->type, '/functional/test');
        $this->assertEquals($userNew->numbers, $userNew2->numbers);
    }

    public function testRemove(): void
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);
        $this->dm->flush();

        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNull($user, 'User must be null after deletion');
    }

    public function testRemoveWithClear(): void
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

    public function testRemoveWithPersist(): void
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

    public function testRemoveNoFlush(): void
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');
    }

    public function testRemoveAndInsertAfterFlush(): void
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);
        $this->dm->flush();

        $user = new User2();
        $user->username = 'test';
        $user->id = '/functional/user';
        $this->dm->persist($user);

        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find(User2::class, '/functional/user');

        $this->assertNotNull($userNew, 'Have to hydrate user object!');
        $this->assertEquals($user->username, $userNew->username);
    }

    public function testRemoveAndReinsert(): void
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);

        $user->username = 'test';
        $user->id = '/functional/user';
        $this->dm->persist($user);

        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find($this->type, '/functional/user');

        $this->assertNotNull($userNew, 'Have to hydrate user object!');
        $this->assertEquals($user->username, $userNew->username);
    }

    public function testRemoveAndInsertBeforeFlush(): void
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);

        $user = new User2();
        $user->username = 'test';
        $user->id = '/functional/user';

        $this->expectException(InvalidArgumentException::class);
        $this->dm->persist($user);
    }

    public function testUpdate1(): void
    {
        $user = new User();
        $user->username = 'test';
        $user->numbers = [1, 2, 3];
        $user->id = '/functional/user2';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find($this->type, '/functional/user2');
        $userNew->username = 'test2';
        $userNew->numbers = [4, 5, 6];
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
        $this->assertEquals($user->numbers, []);

        $user->numbers = [];

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertEquals($user->numbers, []);
    }

    public function testUpdate2(): void
    {
        $user = $this->dm->find($this->type, '/functional/user');
        $user->username = 'new-name';

        $this->dm->flush();
        $this->dm->clear();

        $newUser = $this->dm->find($this->type, '/functional/user');
        $this->assertEquals('new-name', $newUser->username);
    }

    public function testInsertUpdateMultiple(): void
    {
        $user1 = $this->dm->find($this->type, '/functional/user');
        $user1->username = 'new-name';

        $user2 = new User();
        $user2->username = 'test2';
        $user2->id = '/functional/user2222';

        $user3 = new User();
        $user3->username = 'test3';
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

    public function testNullRemovesTheProperty(): void
    {
        $user1 = $this->dm->find($this->type, '/functional/user');
        $user1->note = null;
        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->find($this->type, '/functional/user');
        $this->assertFalse($user2->node->hasProperty('note'));
        $this->assertNull($user2->note);
    }

    public function testNoIdProperty(): void
    {
        $functional = $this->dm->find(null, '/functional');

        $user = new User5();
        $user->username = 'test';
        $user->numbers = [1, 2, 3];
        $user->nodename = 'test';
        $user->parent = $functional;

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find(User5::class, '/functional/test');

        $userNew->username = 'test2';
        $this->dm->flush();

        $userNew = $this->dm->find(User5::class, '/functional/test');

        $this->assertNotNull($userNew, 'Have to hydrate user object!');
        $this->assertEquals('test2', $userNew->username);
        $this->assertEquals($user->numbers, $userNew->numbers);
    }

    public function testAutoId(): void
    {
        $functional = $this->dm->find(null, '/functional');

        $user = new User6();
        $user->username = 'test';
        $user->numbers = [1, 2, 3];
        $user->parent = $functional;

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find(User6::class, '/functional/'.$user->nodename);
        $this->assertEquals($userNew->nodename, $user->nodename);
    }

    public function testAssocProperty(): void
    {
        $user = new User();
        $user->username = 'test';
        $assocArray = ['foo' => 'bar', 'ding' => 'dong', 'dong' => null, 'dang' => null];
        $user->parameters = $assocArray;
        $user->id = '/functional/test';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(null, '/functional/test');

        $this->assertNotNull($user);
        $this->assertEquals($user->parameters, $assocArray);

        $assocArray = ['foo' => 'bar', 'hello' => 'world', 'check' => 'out'];
        $user->parameters = $assocArray;

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(null, '/functional/test');

        $this->assertNotNull($user);
        $this->assertEquals($user->parameters, $assocArray);

        unset($user->parameters['foo'], $assocArray['foo']);

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

    public function testAssocNumberProperty(): void
    {
        $user = new User();
        $user->username = 'test';
        $assocArray = ['foo' => 1, 'ding' => 2];
        $user->assocNumbers = $assocArray;
        $user->id = '/functional/test';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(null, '/functional/test');

        $this->assertNotNull($user);
        $this->assertEquals($user->assocNumbers, $assocArray);
    }

    public function testVersionedDocument(): void
    {
        $user = new VersionTestObj();
        $user->username = 'test';
        $user->numbers = [1, 2, 3];
        $user->id = '/functional/test';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find(null, '/functional/test');
        $this->assertNotNull($userNew, 'Have to hydrate user object!');
        $this->assertEquals($user->username, $userNew->username);
        $this->assertEquals($user->numbers, $userNew->numbers);
    }

    public function testDepth(): void
    {
        $object = new DepthMappingObject();
        $object->id = '/functional/test';
        $this->dm->persist($object);
        $this->dm->flush();
        $this->dm->clear();

        $object = $this->dm->find(null, '/functional/test');
        $this->assertEquals(2, $object->depth);

        NodeHelper::createPath($this->dm->getPhpcrSession(), '/functional/newtest/foobar');
        $this->dm->move($object, '/functional/newtest/foobar/test');
        $this->dm->flush();
        $this->assertEquals(4, $object->depth);
    }

    /**
     * Create a node with a bad name and explicitly persist it without
     * adding it to any parent's children collection.
     */
    public function testIllegalNodename(): void
    {
        $functional = $this->dm->find(null, '/functional');

        $user = new User5();
        $user->username = 'test';
        $user->nodename = 'bad/name';
        $user->parent = $functional;

        $this->expectException(IdException::class);
        $this->dm->persist($user);
    }

    /**
     * Retrieve an existing node and try to move it by assigning it
     * an illegal name.
     */
    public function testIllegalNodenameMove(): void
    {
        $functional = $this->dm->find(null, '/functional');

        $user = new User5();
        $user->username = 'test';
        $user->parent = $functional;
        $user->nodename = 'goodname';
        $this->dm->persist($user);
        $this->dm->flush();

        $user->nodename = 'bad/name';

        $this->expectException(IdException::class);
        $this->dm->flush();
    }

    public function testChangeset(): void
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

    /** @PHPCRODM\Field(type="string") */
    public $username;

    /** @PHPCRODM\Field(type="string", nullable=true) */
    public $note;

    /** @PHPCRODM\Field(type="long", multivalue=true, nullable=true) */
    public $numbers;

    /** @PHPCRODM\Field(type="string", assoc="", nullable=true) */
    public $parameters;

    /** @PHPCRODM\Field(type="long", assoc="", nullable=true) */
    public $assocNumbers;
}

/**
 * @PHPCRODM\Document()
 */
class User2
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Field(type="string") */
    public $username;
}

/**
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\ODM\PHPCR\Functional\User3Repository")
 */
class User3
{
    /** @PHPCRODM\Id(strategy="repository") */
    public $id;

    /** @PHPCRODM\Field(type="string") */
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

    /** @PHPCRODM\Field(type="string") */
    public $username;

    /** @PHPCRODM\Field(type="long", multivalue=true, nullable=true) */
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
     *
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

    /** @PHPCRODM\Field(type="string") */
    public $username;

    /** @PHPCRODM\Field(type="long", multivalue=true, nullable=true) */
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

/**
 * @PHPCRODM\Document
 */
class DepthMappingObject
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Depth */
    public $depth;
}
