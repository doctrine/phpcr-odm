<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\PropertyType;

/**
 * Test the DocumentManager::find method.
 *
 * @group functional
 */
class FindTypeValidationTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * Class name of the document class.
     *
     * @var string
     */
    private $type = TypeUser::class;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager([__DIR__]);
        $node = $this->resetFunctionalNode($this->dm);

        $user = $node->addNode('user');
        $user->setProperty('username', 'lsmith');
        $user->setProperty('note', 'test');
        $user->setProperty('numbers', [3, 1, 2]);
        $user->setProperty('parameters', ['bar', 'dong']);
        $user->setProperty('parameterKey', ['foo', 'ding']);
        $user->setProperty('phpcr:class', $this->type, PropertyType::STRING);
        $this->dm->getPhpcrSession()->save();
    }

    public function testFind(): void
    {
        $user = $this->dm->find($this->type, '/functional/user');

        $this->assertInstanceOf($this->type, $user);
        $this->assertEquals('/functional/user', $user->id);

        $this->assertEquals('lsmith', $user->username);
        $this->assertEquals([3, 1, 2], $user->numbers);

        // subsequent find must find the same object again

        $userAgain = $this->dm->find($this->type, 'functional/user');
        $this->assertInstanceOf($this->type, $user);
        $this->assertSame($user, $userAgain);

        $userAgain = $this->dm->find($this->type, 'functional/user');
        $this->assertInstanceOf($this->type, $user);
        $this->assertSame($user, $userAgain);
    }

    public function testFindWithNamespace(): void
    {
        $user = $this->dm->find(TypeUser::class, 'functional/user');
        $this->assertNotNull($user);
    }

    public function testFindAutoclass(): void
    {
        $user = $this->dm->findDocument('/functional/user');

        $this->assertInstanceOf($this->type, $user);
    }

    /**
     * TypeUser is a superclass of TypeTeamUser.
     */
    public function testInheritance(): void
    {
        $user = new TypeTeamUser();
        $user->username = 'test';
        $user->numbers = [1, 2, 3];
        $user->id = '/functional/test';
        $user->name = 'inheritance';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find($this->type, '/functional/test');

        $this->assertNotNull($userNew, 'Have to hydrate user object!');
        $this->assertEquals($user->username, $userNew->username);
        $this->assertEquals($user->numbers, $userNew->numbers);
        $this->assertEquals($user->name, $userNew->name);
    }

    /**
     * TypeTeamUser is not a superclass of User.
     */
    public function testNotInstanceOf(): void
    {
        $user = $this->dm->find(TypeTeamUser::class, '/functional/user');

        $this->assertNull($user);
    }

    /**
     * TypeTeamUser is not a superclass of User. Still works when loading from cache.
     */
    public function testCacheNotInstanceOf(): void
    {
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertInstanceOf($this->type, $user);

        $user = $this->dm->find(TypeTeamUser::class, '/functional/user');
        $this->assertNull($user);
    }

    /**
     * TypeTeamUser is not a superclass of User.
     */
    public function testManyNotInstanceOf(): void
    {
        $users = $this->dm->findMany(TypeTeamUser::class, ['/functional/user']);

        $this->assertCount(0, $users);
    }
}

#[PHPCR\Document]
class TypeUser
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Node]
    public $node;

    #[PHPCR\Field(type: 'string')]
    public $username;

    #[PHPCR\Field(type: 'string', nullable: true)]
    public $note;

    #[PHPCR\Field(type: 'long', multivalue: true, nullable: true)]
    public $numbers;

    #[PHPCR\Field(type: 'string', assoc: '', nullable: true)]
    public $parameters;

    #[PHPCR\Field(type: 'long', assoc: '', nullable: true)]
    public $assocNumbers;
}

#[PHPCR\Document]
class TypeTeamUser extends TypeUser
{
    #[PHPCR\Field(type: 'string')]
    public $name;
}
