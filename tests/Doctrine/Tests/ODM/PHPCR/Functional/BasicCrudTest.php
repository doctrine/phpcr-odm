<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface,
    Doctrine\ODM\PHPCR\DocumentRepository;

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
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);

        $user = $this->node->addNode('user');
        $user->setProperty('username', 'lsmith');
        $user->setProperty('numbers', array(3, 1, 2));
        $user->setProperty('phpcr:alias', 'user', \PHPCR\PropertyType::STRING);
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

        $userNew->numbers = array(1,2);
        $this->dm->flush();
        $this->dm->clear();

        $userNew2 = $this->dm->find($this->type, '/functional/test');
        $this->assertEquals($userNew->numbers->toArray(), $userNew2->numbers->toArray());
    }

    public function testDelete()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertNull($user, 'User must be null after deletion');
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

        $this->assertNotEquals($user, $userNew);
        $this->assertNotEquals($userNew, $userNew2);
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
        $this->assertInstanceOf($this->type, $user);

        $this->dm->getConfiguration()->setValidateDoctrineMetadata(true);
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertInstanceOf($this->type, $user);

        $this->setExpectedException('InvalidArgumentException');
        $user = $this->dm->find($this->type.'2', '/functional/user');
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

    public function testKeepTrackOfUnmappedData()
    {
        $this->markTestIncomplete('Update to remove the use of the httpClient');

        $data =  array(
            '_id' => "2",
            'username' => 'beberlei',
            'email' => 'kontakt@beberlei.de',
            'address' => array('city' => 'Bonn', 'country' => 'DE'),
            'doctrine_metadata' => array('type' => $this->type)
        );
        $resp = $httpClient->request('PUT', '/' . $this->dm->getConfiguration()->getDatabase() . '/2', json_encode($data));
        $this->assertEquals(201, $resp->status);

        $user = $this->dm->find($this->type, 2);
        $this->assertInstanceOf($this->type, $user);
        $this->assertEquals('beberlei', $user->username);

        $user->username = 'beberlei2';
        $this->dm->flush();

        $resp = $httpClient->request('GET', '/' . $this->dm->getConfiguration()->getDatabase() . '/2');
        $this->assertEquals(200, $resp->status);

        $data['username'] = 'beberlei2';

        ksort($resp->body);
        ksort($data);

        $this->assertEquals($data, $resp->body);
    }
}

/**
 * @Document(alias="user")
 */
class User
{
    /** @Id */
    public $id;
    /** @Node */
    public $node;
    /** @String(name="username") */
    public $username;
    /** @Int(name="numbers", multivalue=true) */
    public $numbers;
}

/**
 * @Document(alias="user2")
 */
class User2
{
    /** @Id */
    public $id;
    /** @String(name="username") */
    public $username;
}

/**
 * @Document(repositoryClass="Doctrine\Tests\ODM\PHPCR\Functional\User3Repository", alias="user3")
 */
class User3
{
    /** @Id(strategy="repository") */
    public $id;
    /** @String(name="username") */
    public $username;
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
        return 'functional/'.$document->username;
    }
}
