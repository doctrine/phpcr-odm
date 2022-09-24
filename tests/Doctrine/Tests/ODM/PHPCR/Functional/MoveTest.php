<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\Tests\Models\CMS\CmsTeamUser;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\PropertyType;

/**
 * @group functional
 */
class MoveTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    private $type = CmsUser::class;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager([__DIR__]);
        $node = $this->resetFunctionalNode($this->dm);

        $user = $node->addNode('lsmith');
        $user->setProperty('username', 'lsmith');
        $user->setProperty('numbers', [3, 1, 2]);
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

    public function testMove(): void
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

    public function testMoveWithClear(): void
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

    public function testMoveWithPersist(): void
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

    public function testMoveFirstPersist(): void
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

    public function testMoveThenRemove(): void
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

    public function testMoveNoFlush(): void
    {
        $this->dm->clear();
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNotNull($user, 'User must exist');

        $this->dm->move($user, '/functional/user2');
        $user = $this->dm->find($this->type, '/functional/lsmith');
        $this->assertNotNull($user, 'User must exist');
    }

    public function testMoveWithChild(): void
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

    public function testRemoveThenMove(): void
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

    public function testMoveUpdateFields(): void
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

    public function testMoveToRootByParent(): void
    {
        $user2 = new CmsTeamUser();
        $user2->username = 'dbu';
        $user2->parent = $this->dm->find(null, '/functional/lsmith');
        $this->dm->persist($user2);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(null, '/functional/lsmith/dbu');
        $this->assertInstanceOf(CmsTeamUser::class, $user);
        $root = $this->dm->find(null, '/');
        $user->setParentDocument($root);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(null, '/dbu');
        $this->assertInstanceOf(CmsTeamUser::class, $user);
    }
}
