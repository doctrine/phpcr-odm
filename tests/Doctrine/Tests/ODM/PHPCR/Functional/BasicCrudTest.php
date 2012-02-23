<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface,
    Doctrine\ODM\PHPCR\DocumentRepository,
    Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @group functional
 */
class BasicCrudTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    private $type;

    private $node;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\User';
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);

        $user = $this->node->addNode('user');
        $user->setProperty('username', 'lsmith');
        $user->setProperty('numbers', array(3, 1, 2));
        $user->setProperty('phpcr:class', $this->type, \PHPCR\PropertyType::STRING);
        $this->dm->getPhpcrSession()->save();
    }

    public function testFind()
    {
        $user = $this->dm->find($this->type, '/functional/user');

        $this->assertInstanceOf($this->type, $user);
        $this->assertEquals('/functional/user', $user->id);

        $this->assertEquals('lsmith', $user->username);
        $this->assertEquals(array(3, 1, 2), $user->numbers->toArray());
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
        $this->assertEquals($user->numbers->toArray(), $userNew->numbers->toArray());
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

    public function testFindNonPersisted()
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

    public function testInsertWithCustomIdStrategy()
    {
        $user = new User3();
        $user->username = "test3";

        $this->dm->persist($user);
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

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find($this->type, '/functional/test');
        $this->assertEquals($user->numbers->toArray(), $userNew->numbers->toArray());

        $userNew->numbers = array(1, 2);
        $this->dm->flush();
        $this->dm->clear();

        $userNew2 = $this->dm->find($this->type, '/functional/test');
        $this->assertEquals($userNew->numbers->toArray(), $userNew2->numbers->toArray());
    }

    public function testMove()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->move($user, '/functional/user2');
        $this->dm->flush();

        $user = $this->dm->find($this->type, '/functional/user2');
        $this->assertNotNull($user, 'User must exist');

        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNull($user, 'User must not exist');
    }

    public function testMoveWithClear()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->move($user, '/functional/user2');
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find($this->type, '/functional/user2');
        $this->assertNotNull($user, 'User must exist');
    }

    public function testMoveWithPersist()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->move($user, '/functional/user2');
        $this->dm->persist($user);
        $this->dm->flush();

        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');
    }

    public function testMoveThenRemove()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->move($user, '/functional/user2');
        $this->dm->remove($user);
        $this->dm->flush();

        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNull($user, 'User must be null after deletion');
        $user = $this->dm->find($this->type, '/functional/user2');
        $this->assertNull($user, 'User must be null after deletion');
    }

    public function testMoveNoFlush()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->move($user, '/functional/user2');
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');
    }

    public function testMoveWithChild()
    {
        $this->dm->clear();
        $user1 = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user1, 'User must exist');

        $user2 = new TeamUser();
        $user2->username = 'jwage';
        $user2->id = '/functional/user/team';
        $user2->parent = $user1;
        $user3 = new TeamUser();
        $user3->username = 'beberlei';
        $user3->id = '/functional/user/team/team';
        $user3->parent = $user2;

        $this->dm->persist($user3);

        $this->dm->flush();

        $user1 = $this->dm->find($this->type, '/functional/user');
        $this->dm->move($user1, '/functional/user2');
        $this->dm->flush();

        $this->dm->clear();

        $user1 = $this->dm->find($this->type, '/functional/user2');

        $user = $this->dm->find($this->type, '/functional/user2');
        $this->assertNotNull($user, 'User must exist');
        $user = $this->dm->find($this->type, '/functional/user2/team');
        $this->assertNotNull($user, 'User must exist');
        $user = $this->dm->find($this->type, '/functional/user2/team/team');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->move($user1, '/functional/user');
        $this->dm->flush();

        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');
        $user = $this->dm->find($this->type, '/functional/user/team');
        $this->assertNotNull($user, 'User must exist');
        $user = $this->dm->find($this->type, '/functional/user/team/team');
        $this->assertNotNull($user, 'User must exist');
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

    public function testRemoveThenMove()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);
        $this->dm->move($user, '/functional/user2');
        $this->dm->flush();

        $user = $this->dm->find($this->type, '/functional/user2');
        $this->assertNotNull($user, 'User must exist');

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

        $userNew = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User2', '/functional/user');

        $this->assertNotNull($userNew, "Have to hydrate user object!");
        $this->assertEquals($user->username, $userNew->username);
    }

    /**
     * @expectedException \InvalidArgumentException
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

    public function testFindTypeValidation()
    {
        // hackish: forcing the class metadata to be loaded for those two classes so that the alias mapper finds them
        $this->dm->getRepository($this->type);
        $this->dm->getRepository($this->type.'2');
        // --

        $this->dm->getConfiguration()->setValidateDoctrineMetadata(false);
        $user = $this->dm->find($this->type.'2', '/functional/user');
        $this->assertNotInstanceOf($this->type, $user);

        $this->dm->getConfiguration()->setValidateDoctrineMetadata(true);
        $this->setExpectedException('InvalidArgumentException');
        $this->dm->find($this->type, '/functional/user');
    }

    public function testNullRemovesTheProperty()
    {
        $user1 = $this->dm->find($this->type, '/functional/user');
        $user1->username = null;
        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->find($this->type, '/functional/user');
        $this->assertFalse($user2->node->hasProperty('username'));
        $this->assertNull($user2->username);
    }

    public function testInheritance()
    {
        $user = new User4();
        $user->username = "test";
        $user->numbers = array(1, 2, 3);
        $user->id = '/functional/test';
        $user->name = 'inheritance';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User4', '/functional/test');

        $this->assertNotNull($userNew, "Have to hydrate user object!");
        $this->assertEquals($user->username, $userNew->username);
        $this->assertEquals($user->numbers->toArray(), $userNew->numbers->toArray());
        $this->assertEquals($user->name, $userNew->name);
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

        $userNew = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User4', '/functional/test');

        $userNew->username = "test2";
        $this->dm->flush();

        $userNew = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User4', '/functional/test');

        $this->assertNotNull($userNew, "Have to hydrate user object!");
        $this->assertEquals('test2', $userNew->username);
        $this->assertEquals($user->numbers->toArray(), $userNew->numbers->toArray());
    }

    public function testFlushSingleDocument()
    {
        $user1 = new User();
        $user1->username = 'romanb';
        $user1->id = '/functional/test';
        $user2 = new User();
        $user2->username = 'jwage';
        $user2->id = '/functional/test2';
        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();
        $this->dm->clear();

        $user1 = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User', '/functional/test');
        $this->assertEquals('romanb', $user1->username);

        $user2 = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User', '/functional/test2');
        $this->assertEquals('jwage', $user2->username);

        $user1->username = 'changed';
        $user2->username = 'changed';
        $this->dm->flush($user1);
        $this->dm->clear();

        $check = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User', '/functional/test');
        $this->assertEquals('changed', $check->username);

        $check = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User', '/functional/test2');
        $this->assertEquals('jwage', $check->username);
    }

    public function testFlushSingleDocumentThenFlush()
    {
        $user1 = new User();
        $user1->username = 'romanb';
        $user1->id = '/functional/test';
        $user2 = new User();
        $user2->username = 'jwage';
        $user2->id = '/functional/test2';
        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();

        $user1->username = 'changed';
        $user2->username = 'changed';
        $this->dm->flush($user1);

        $check = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User', '/functional/test');
        $this->assertEquals('changed', $check->username);

        $this->dm->flush();

        $check = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User', '/functional/test2');
        $this->assertEquals('changed', $check->username);
    }

    public function testFlushSingleDocumentWithParent()
    {
        $user1 = new User();
        $user1->username = 'romanb';
        $user1->id = '/functional/test';
        $user2 = new TeamUser();
        $user2->username = 'jwage';
        $user2->id = '/functional/test/team';
        $user2->parent = $user1;
        $user3 = new TeamUser();
        $user3->username = 'beberlei';
        $user3->id = '/functional/test/team/team';
        $user3->parent = $user2;
        $this->dm->persist($user3);
        $this->dm->flush($user3);

        $user1 = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User', '/functional/test');
        $this->assertEquals('romanb', $user1->username);

        $user2 = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User', '/functional/test/team');
        $this->assertEquals('jwage', $user2->username);

        $user3 = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User', '/functional/test/team/team');
        $this->assertEquals('beberlei', $user3->username);

        $user1->username = 'changed';
        $user2->username = 'changed';
        $user3->username = 'changed';
        $this->dm->flush($user3);

        $user1 = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User', '/functional/test');
        $this->assertEquals('changed', $user1->username);

        $user2 = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User', '/functional/test/team');
        $this->assertEquals('changed', $user2->username);

        $user3 = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\User', '/functional/test/team/team');
        $this->assertEquals('changed', $user3->username);
    }

    public function testDetach()
    {
        $user = $this->dm->find($this->type, '/functional/user');
        $user->username = "new-name";

        $this->dm->detach($user);
        $this->dm->flush();
        $this->dm->clear();

        $newUser = $this->dm->find($this->type, '/functional/user');
        $this->assertEquals('lsmith', $newUser->username);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDetachWithPerist()
    {
        $user = $this->dm->find($this->type, '/functional/user');
        $user->username = "new-name";

        $this->dm->detach($user);
        $this->dm->persist($user);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDetachWithMove()
    {
        $user = $this->dm->find($this->type, '/functional/user');
        $user->username = "new-name";

        $this->dm->detach($user);
        $this->dm->move($user, '/functional/user2');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDetachWithRemove()
    {
        $user = $this->dm->find($this->type, '/functional/user');
        $user->username = "new-name";

        $this->dm->detach($user);
        $this->dm->remove($user);
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
    /** @PHPCRODM\String(name="username") */
    public $username;
    /** @PHPCRODM\Int(name="numbers", multivalue=true) */
    public $numbers;
}

/**
 * @PHPCRODM\Document()
 */
class User2
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String(name="username") */
    public $username;
}

/**
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\ODM\PHPCR\Functional\User3Repository")
 */
class User3
{
    /** @PHPCRODM\Id(strategy="repository") */
    public $id;
    /** @PHPCRODM\String(name="username") */
    public $username;
}

/**
 * @PHPCRODM\Document()
 */
class User4 extends User
{
    /** @PHPCRODM\String(name="name") */
    public $name;
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
    /** @PHPCRODM\String(name="username") */
    public $username;
    /** @PHPCRODM\Int(name="numbers", multivalue=true) */
    public $numbers;
}

class User3Repository extends DocumentRepository implements RepositoryIdInterface
{
    /**
     * Generate a document id
     *
     * @param object $document
     * @return string
     */
    public function generateId($document)
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

