<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use PHPCR\PropertyType;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * Test the DocumentManager::find method.
 *
 * @group functional
 */
class FindTypeValidationTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
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
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\TypeUser';
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

    public function testFind()
    {
        $user = $this->dm->find($this->type, '/functional/user');

        $this->assertInstanceOf($this->type, $user);
        $this->assertEquals('/functional/user', $user->id);

        $this->assertEquals('lsmith', $user->username);
        $this->assertEquals(array(3, 1, 2), $user->numbers);

        // subsequent find must find the same object again

        $userAgain = $this->dm->find($this->type, 'functional/user');
        $this->assertInstanceOf($this->type, $user);
        $this->assertSame($user, $userAgain);

        $userAgain = $this->dm->find($this->type, 'functional/user');
        $this->assertInstanceOf($this->type, $user);
        $this->assertSame($user, $userAgain);
    }

    public function testFindWithNamespace()
    {
        $config = $this->dm->getConfiguration();
        $config->addDocumentNamespace('Foobar', 'Doctrine\Tests\ODM\PHPCR\Functional');

        $user = $this->dm->find('Foobar:TypeUser', 'functional/user');
        $this->assertNotNull($user);
    }

    public function testFindAutoclass()
    {
        $user = $this->dm->find(null, '/functional/user');

        $this->assertInstanceOf($this->type, $user);
    }

    /**
     * TypeUser is a superclass of TypeTeamUser
     */
    public function testInheritance()
    {
        $user = new TypeTeamUser();
        $user->username = "test";
        $user->numbers = array(1, 2, 3);
        $user->id = '/functional/test';
        $user->name = 'inheritance';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find($this->type, '/functional/test');

        $this->assertNotNull($userNew, "Have to hydrate user object!");
        $this->assertEquals($user->username, $userNew->username);
        $this->assertEquals($user->numbers, $userNew->numbers);
        $this->assertEquals($user->name, $userNew->name);
    }

    /**
     * TypeTeamUser is not a superclass of User
     */
    public function testNotInstanceOf()
    {
        $user = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\TypeTeamUser', '/functional/user');

        $this->assertTrue(null === $user, get_class($user));
    }

    /**
     * TypeTeamUser is not a superclass of User. Still works when loading from cache.
     */
    public function testCacheNotInstanceOf()
    {
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertInstanceOf($this->type, $user);

        $user = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\TypeTeamUser', '/functional/user');
        $this->assertTrue(null === $user, get_class($user));
    }

    /**
     * TypeTeamUser is not a superclass of User
     */
    public function testManyNotInstanceOf()
    {
        $users = $this->dm->findMany('Doctrine\Tests\ODM\PHPCR\Functional\TypeTeamUser', array('/functional/user'));

        $this->assertCount(0, $users);
    }
}

/**
 * @PHPCRODM\Document()
 */
class TypeUser
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
class TypeTeamUser extends TypeUser
{
    /** @PHPCRODM\String */
    public $name;
}
