<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\Tests\Models\CMS\CmsTeamUser;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode as QBConstants;
use Doctrine\ODM\PHPCR\Query\Builder\SourceDocument;
use Doctrine\ODM\PHPCR\Query\Builder\From;
use PHPCR\NodeInterface;

/**
 * @group functional
 */
class DocumentRepositoryTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;
    /**
     * Class name of the document class
     * @var string
     */
    private $type;

    /**
     * @var NodeInterface
     */
    private $node;

    public function setUp(): void
    {
        $this->type = CmsUser::class;
        $this->dm = $this->createDocumentManager();

        $session = $this->dm->getPhpcrSession();
        $root = $session->getNode('/');
        if ($root->hasNode('functional')) {
            $root->getNode('functional')->remove();
            $session->save();
        }
        $this->node = $root->addNode('functional');
        $session->save();
    }

    public function testCreateQueryBuilder()
    {
        $rep = $this->dm->getRepository(CmsUser::class);
        $qb = $rep->createQueryBuilder('a');

        $from = $qb->getChildOfType(QBConstants::NT_FROM);
        $this->assertInstanceOf(From::class, $from);
        $source = $from->getChildOfType(QBConstants::NT_SOURCE);
        $this->assertInstanceOf(SourceDocument::class, $source);

        $this->assertEquals('a', $source->getAlias());
        $this->assertEquals('a', $qb->getPrimaryAlias());

        $this->assertEquals(CmsUser::class, $source->getDocumentFqn());
    }

    public function testLoadMany()
    {
        $user1 = new CmsUser();
        $user1->username = 'beberlei';
        $user1->status = 'active';
        $user1->name = 'Benjamin';

        $user2 = new CmsUser();
        $user2->username = 'lsmith';
        $user2->status = 'active';
        $user2->name = 'Lukas';

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();

        $users = $this->dm->getRepository(CmsUser::class)->findMany(array($user1->id, $user2->id));
        $this->assertSame($user1, $users['/functional/beberlei']);
        $this->assertSame($user2, $users['/functional/lsmith']);

        $users = $this->dm->getRepository(CmsUser::class)->findMany(array($user1->node->getIdentifier(), substr($user2->id, 1)));
        $this->assertSame($user1, $users['/functional/beberlei']);
        $this->assertSame($user2, $users['/functional/lsmith']);

        $this->dm->clear();

        $users = $this->dm->getRepository(CmsUser::class)->findMany(array($user1->id, $user2->id));
        $this->assertTrue(isset($users['/functional/beberlei']));
        $this->assertTrue(isset($users['/functional/lsmith']));
        $this->assertInstanceOf(CmsUser::class, $users['/functional/beberlei']);
        $this->assertInstanceOf(CmsUser::class, $users['/functional/lsmith']);
        $this->assertEquals($user1->username, $users['/functional/beberlei']->username);
        $this->assertEquals($user2->username, $users['/functional/lsmith']->username);

        $this->dm->clear();

        // read second document into memory
        $this->dm->getRepository(CmsUser::class)->find($user2->id);
        $users = $this->dm->getRepository(CmsUser::class)->findMany(array($user1->id, $user2->id));
        $this->assertEquals('/functional/beberlei', $users->key(), 'Documents are not returned in the order they were requested');
    }

    public function testFindBy()
    {
        $user1 = new CmsUser();
        $user1->username = "beberlei";
        $user1->status = "active";
        $user1->name = "Benjamin";

        $user2 = new CmsUser();
        $user2->username = "lsmith";
        $user2->status = "active";
        $user2->name = "Lukas";

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();

        $users1 = $this->dm->getRepository(CmsUser::class)->findBy(array('username' =>'beberlei'));
        $this->assertCount(1, $users1);
        $this->assertEquals($user1->username, $users1['/functional/beberlei']->username);

        $users2 = $this->dm->getRepository(CmsUser::class)->findBy(array('status' =>'active'));
        $this->assertCount(2, $users2);

        $users3 = $this->dm->getRepository(CmsUser::class)->findBy(array('status' =>'active'), null, 1);
        $this->assertCount(1, $users3);

        $users4 = $this->dm->getRepository(CmsUser::class)->findBy(array('status' =>'active'), array('name' => 'asc'), 2, 0);
        $this->assertEquals('/functional/beberlei', $users4->key());

        $users5 = $this->dm->getRepository(CmsUser::class)->findBy(array('status' =>'active'), array('name' => 'asc'), 2, 1);
        $this->assertEquals('/functional/lsmith', $users5->key());

        // test descending order
        $users6 = $this->dm->getRepository(CmsUser::class)->findBy(array('status' =>'active'), array('name' => 'desc'));
        $this->assertEquals('/functional/lsmith', $users6->key());
    }

    public function testFindByOnNodename()
    {
        $parent = new CmsUser();
        $parent->username = "lsmith";
        $parent->status = "active";
        $parent->name = "Lukas";

        $user = new CmsTeamUser();
        $user->username = "beberlei";
        $user->status = "active";
        $user->name = "Benjamin";
        $user->parent = $parent;

        $this->dm->persist($user);
        $this->dm->flush();

        $users = $this->dm->getRepository(CmsTeamUser::class)->findBy(array('nodename' => 'beberlei'));
        $this->assertCount(1, $users);
        $this->assertEquals($user->username, $users['/functional/lsmith/beberlei']->username);
    }

    public function testFindByOrderNonExistentDirectionString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->getRepository(CmsTeamUser::class)->findBy(array('nodename' =>'beberlei'), array('username' =>'nowhere'));
    }

    public function testFindByOrderNodename()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->getRepository(CmsTeamUser::class)->findBy(array('nodename' =>'beberlei'), array('nodename' =>'asc'));
    }

    public function testFindByOnAssociation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->getRepository(CmsTeamUser::class)->findBy(array('parent' =>'/foo'));
    }

    public function testFindByOrderAssociation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->getRepository(CmsTeamUser::class)->findBy(array('username' =>'beberlei'), array('parent' => 'asc'));
    }

    public function testFindOneBy()
    {
        $user1 = new CmsUser();
        $user1->username = 'beberlei';
        $user1->status = 'active';
        $user1->name = 'Benjamin';

        $user2 = new CmsUser();
        $user2->username = 'lsmith';
        $user2->status = 'active';
        $user2->name = 'Lukas';

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();

        $users1 = $this->dm->getRepository(CmsUser::class)->findOneBy(array('username' =>'beberlei'));
        $this->assertEquals($user1->username, $users1->username);

        $users2 = $this->dm->getRepository(CmsUser::class)->findOneBy(array('username' =>'obama'));
        $this->assertEquals(null, $users2);
    }
}
