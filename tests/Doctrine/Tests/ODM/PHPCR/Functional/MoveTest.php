<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

use PHPCR\PropertyType;

use Doctrine\Tests\Models\CMS\CmsTeamUser;

/**
 * @group functional
 */
class MoveTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    private $type;

    private $node;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\Models\CMS\CmsUser';
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);

        $user = $this->node->addNode('lsmith');
        $user->setProperty('username', 'lsmith');
        $user->setProperty('numbers', array(3, 1, 2));
        $user->setProperty('phpcr:class', $this->type, PropertyType::STRING);
        $this->dm->getPhpcrSession()->save();

        // remove node for root tests
        $session = $this->dm->getPhpcrSession();
        $root = $session->getNode('/');
        if ($root->hasNode('dbu')) {
            $root->getNode('dbu')->remove();
            $session->save();
        }
    }

    public function testMove()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->move($user, '/functional/user2');
        $this->dm->flush();

        $user = $this->dm->find($this->type, '/functional/user2');
        $this->assertNotNull($user, 'User must exist');

        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNull($user, 'User must not exist');
    }

    public function testMoveWithClear()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->move($user, '/functional/user2');
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNotNull($user, 'User must exist');
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find($this->type, '/functional/user2');
        $this->assertNotNull($user, 'User must exist');
    }

    public function testMoveWithPersist()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->move($user, '/functional/user2');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find($this->type, '/functional/user2');
        $this->assertNotNull($user, 'User must exist');
    }

    public function testMoveFirstPersist()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNotNull($user, 'User must exist');

        $user->username = 'new name';
        $this->dm->persist($user);
        $this->dm->move($user, '/functional/user2');
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find($this->type, '/functional/user2');
        $this->assertNotNull($user, 'User must exist');
        $this->assertEquals('new name', $user->username);
    }

    public function testMoveThenRemove()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->move($user, '/functional/user2');
        $this->dm->remove($user);
        $this->dm->flush();

        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNull($user, 'User must be null after deletion');
        $user = $this->dm->find($this->type, '/functional/user2');
        $this->assertNull($user, 'User must be null after deletion');
    }

    public function testMoveNoFlush()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->move($user, '/functional/user2');
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNotNull($user, 'User must exist');
    }

    public function testMoveWithChild()
    {
        $this->dm->clear();
        $user1 = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNotNull($user1, 'User must exist');

        $user2 = new CmsTeamUser();
        $user2->username = 'jwage';
        $user2->parent = $user1;
        $user3 = new CmsTeamUser();
        $user3->username = 'beberlei';
        $user3->parent = $user2;

        $this->dm->persist($user3);

        $this->dm->flush();

        $user1 = $this->dm->find($this->type, '/functional/lsmith');
        $this->dm->move($user1, '/functional/user2');
        $this->dm->flush();

        $this->dm->clear();

        $user1 = $this->dm->find($this->type, '/functional/user2');

        $user = $this->dm->find($this->type, '/functional/user2');
        $this->assertNotNull($user, 'User must exist');
        $user = $this->dm->find($this->type, '/functional/user2/jwage');
        $this->assertNotNull($user, 'User must exist');
        $user = $this->dm->find($this->type, '/functional/user2/jwage/beberlei');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->move($user1, '/functional/lsmith');
        $this->dm->flush();

        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNotNull($user, 'User must exist');
        $user = $this->dm->find($this->type, '/functional/lsmith/jwage');
        $this->assertNotNull($user, 'User must exist');
        $user = $this->dm->find($this->type, '/functional/lsmith/jwage/beberlei');
        $this->assertNotNull($user, 'User must exist');
    }

    public function testRemoveThenMove()
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->remove($user);
        $this->dm->move($user, '/functional/user2');
        $this->dm->flush();

        $user = $this->dm->find($this->type, '/functional/user2');
        $this->assertNotNull($user, 'User must exist');

        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNull($user, 'User must be null after deletion');
    }

    public function testMoveUpdateFields()
    {
        $this->dm->clear();
        $user1 = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNotNull($user1, 'User must exist');

        $user2 = new CmsTeamUser();
        $user2->username = 'jwage';
        $user2->parent = $user1;
        $user3 = new CmsTeamUser();
        $user3->username = 'beberlei';
        $user3->parent = $user2;

        $this->dm->persist($user3);

        $this->dm->flush();

        // property is updated after flush
        $this->assertEquals('beberlei', $user3->nodename);
        $this->assertSame($user2, $user3->parent);

        $this->dm->move($user3, '/functional/lsmith/user');
        $this->dm->flush();

        $this->assertEquals('user', $user3->nodename);
        $this->assertSame($user1, $user3->parent);
    }

    public function testMoveToRootByParent()
    {
        $user2 = new CmsTeamUser();
        $user2->username = 'dbu';
        $user2->parent = $this->dm->find(null, '/functional/lsmith');
        $this->dm->persist($user2);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(null, '/functional/lsmith/dbu');
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsTeamUser', $user);
        $root = $this->dm->find(null, '/');
        $user->setParentDocument($root);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(null, '/dbu');
        $this->assertInstanceOf('Doctrine\Tests\Models\CMS\CmsTeamUser', $user);
    }
}
