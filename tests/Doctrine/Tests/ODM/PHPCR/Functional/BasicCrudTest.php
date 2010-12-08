<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

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

        $session = $this->dm->getPhpcrSession();
        $root = $session->getNode('/');
        if ($root->hasNode('functional')) {
            $root->getNode('functional')->remove();
            $session->save();
        }
        $this->node = $root->addNode('functional');
        $user = $this->node->addNode('user');
        $user->setProperty('username', 'lsmith');
        $user->setProperty('_doctrine_alias', 'user');
        $session->save();
    }

    public function testFind()
    {
        $user = $this->dm->find($this->type, '/functional/user');

        $this->assertType($this->type, $user);
        $this->assertEquals('/functional/user', $user->path);
        $this->assertEquals('lsmith', $user->username);
    }

    public function testInsert()
    {
        $user = new User();
        $user->username = "test";

        $this->dm->persist($user, '/functional/test');
        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find($this->type, '/functional/test');

        $this->assertNotNull($userNew, "Have to hydrate user object!");
        $this->assertEquals($user->username, $userNew->username);
    }

    public function testDelete()
    {
        $user = $this->dm->find($this->type, '/functional/user');

        $this->dm->remove($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->setExpectedException('PHPCR\PathNotFoundException');
        $this->dm->find($this->type, '/functional/user');
    }

    public function testRemove()
    {
        $user = $this->dm->find($this->type, '/functional/user');

        $this->dm->remove($user);
        $this->dm->flush();

        $this->setExpectedException('PHPCR\PathNotFoundException');
        $this->dm->find($this->type, '/functional/user');
    }

    public function testUpdate1()
    {
        $user = new User();
        $user->username = "test";

        $this->dm->persist($user, '/functional/user2');
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find($this->type, '/functional/user2');
        $user->username = "test2";

        $this->dm->flush();
        $this->dm->clear();

        $userNew = $this->dm->find($this->type, '/functional/user2');

        $this->assertEquals($user->username, $userNew->username);
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

        $user3 = new User();
        $user3->username = "test3";

        $this->dm->persist($user2, '/functional/user2222');
        $this->dm->persist($user3, '/functional/user3333');
        $this->dm->flush();
        $this->dm->clear();

        $pUser1 = $this->dm->find($this->type, '/functional/user');
        $pUser2 = $this->dm->find($this->type, '/functional/user2222');
        $pUser3 = $this->dm->find($this->type, '/functional/user3333');

        $this->assertEquals('/functional/user', $pUser1->path);
        $this->assertEquals('new-name', $pUser1->username);
        $this->assertEquals('/functional/user2222', $pUser2->path);
        $this->assertEquals('test2', $pUser2->username);
        $this->assertEquals('/functional/user3333', $pUser3->path);
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
        $this->assertType($this->type, $user);

        $this->dm->getConfiguration()->setValidateDoctrineMetadata(true);
        $user = $this->dm->find($this->type, '/functional/user');
        $this->assertType($this->type, $user);

        $this->setExpectedException('InvalidArgumentException');
        $user = $this->dm->find($this->type.'2', '/functional/user');
    }

    public function testNullConversionHandledAutomatically()
    {
        $user1 = $this->dm->find($this->type, '/functional/user');
        $user1->username = null;

        $this->dm->flush();
        $this->dm->clear();

        $pUser1 = $this->dm->find($this->type, '/functional/user');

        $this->assertNull($pUser1->username);
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
        $this->assertType($this->type, $user);
        $this->assertEquals('beberlei', $user->username);

        $user->username = 'beberlei2';
        $this->dm->flush();

        $resp = $httpClient->request('GET', '/' . $this->dm->getConfiguration()->getDatabase() . '/2');
        $this->assertEquals(200, $resp->status);

        $data['username'] = 'beberlei2';

        ksort($resp->body);
        ksort($data);
        unset($resp->body['_rev']);

        $this->assertEquals($data, $resp->body);
    }
}

/**
 * @Document(alias="user")
 */
class User
{
    /** @Path */
    public $path;
    /** @String(name="username") */
    public $username;
}

/**
 * @Document(alias="user2")
 */
class User2
{
    /** @Path */
    public $path;
    /** @String(name="username") */
    public $username;
}