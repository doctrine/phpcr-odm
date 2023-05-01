<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode as QBConstants;
use Doctrine\ODM\PHPCR\Query\Builder\From;
use Doctrine\ODM\PHPCR\Query\Builder\SourceDocument;
use Doctrine\Tests\Models\CMS\CmsTeamUser;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;

/**
 * @group functional
 */
class DocumentRepositoryTest extends PHPCRFunctionalTestCase
{
    private DocumentManager $dm;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager();

        $session = $this->dm->getPhpcrSession();
        $root = $session->getNode('/');
        if ($root->hasNode('functional')) {
            $root->getNode('functional')->remove();
            $session->save();
        }
        $root->addNode('functional');
        $session->save();
    }

    public function testCreateQueryBuilder(): void
    {
        $rep = $this->dm->getRepository(CmsUser::class);
        $this->assertInstanceOf(DocumentRepository::class, $rep);
        $qb = $rep->createQueryBuilder('a');

        $from = $qb->getChildOfType(QBConstants::NT_FROM);
        $this->assertInstanceOf(From::class, $from);
        $source = $from->getChildOfType(QBConstants::NT_SOURCE);
        $this->assertInstanceOf(SourceDocument::class, $source);

        $this->assertEquals('a', $source->getAlias());
        $this->assertEquals('a', $qb->getPrimaryAlias());

        $this->assertEquals(CmsUser::class, $source->getDocumentFqn());
    }

    public function testLoadMany(): void
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

        $usersRepository = $this->dm->getRepository(CmsUser::class);
        $this->assertInstanceOf(DocumentRepository::class, $usersRepository);
        $users = $usersRepository->findMany([$user1->id, $user2->id]);
        $this->assertSame($user1, $users['/functional/beberlei']);
        $this->assertSame($user2, $users['/functional/lsmith']);

        $users = $usersRepository->findMany([$user1->node->getIdentifier(), substr($user2->id, 1)]);
        $this->assertSame($user1, $users['/functional/beberlei']);
        $this->assertSame($user2, $users['/functional/lsmith']);

        $this->dm->clear();

        $users = $usersRepository->findMany([$user1->id, $user2->id]);

        $this->assertArrayHasKey('/functional/beberlei', $users);
        $this->assertArrayHasKey('/functional/lsmith', $users);
        $this->assertInstanceOf(CmsUser::class, $users['/functional/beberlei']);
        $this->assertInstanceOf(CmsUser::class, $users['/functional/lsmith']);
        $this->assertEquals($user1->username, $users['/functional/beberlei']->username);
        $this->assertEquals($user2->username, $users['/functional/lsmith']->username);

        $this->dm->clear();

        // read second document into memory
        $usersRepository->find($user2->id);
        $users = $usersRepository->findMany([$user1->id, $user2->id]);
        $this->assertEquals('/functional/beberlei', $users->key(), 'Documents are not returned in the order they were requested');
    }

    public function testFindBy(): void
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

        $userRepository = $this->dm->getRepository(CmsUser::class);
        $this->assertInstanceOf(DocumentRepository::class, $userRepository);
        $users1 = $userRepository->findBy(['username' => 'beberlei']);
        $this->assertCount(1, $users1);
        $this->assertEquals($user1->username, $users1['/functional/beberlei']->username);

        $users2 = $userRepository->findBy(['status' => 'active']);
        $this->assertCount(2, $users2);

        $users3 = $userRepository->findBy(['status' => 'active'], null, 1);
        $this->assertCount(1, $users3);

        $users4 = $userRepository->findBy(['status' => 'active'], ['name' => 'asc'], 2, 0);
        $this->assertInstanceOf(Collection::class, $users4);
        $this->assertEquals('/functional/beberlei', $users4->key());

        $users5 = $userRepository->findBy(['status' => 'active'], ['name' => 'asc'], 2, 1);
        $this->assertInstanceOf(Collection::class, $users5);
        $this->assertEquals('/functional/lsmith', $users5->key());

        // test descending order
        $users6 = $userRepository->findBy(['status' => 'active'], ['name' => 'desc']);
        $this->assertInstanceOf(Collection::class, $users6);
        $this->assertEquals('/functional/lsmith', $users6->key());
    }

    public function testFindByOnNodename(): void
    {
        $parent = new CmsUser();
        $parent->username = 'lsmith';
        $parent->status = 'active';
        $parent->name = 'Lukas';

        $user = new CmsTeamUser();
        $user->username = 'beberlei';
        $user->status = 'active';
        $user->name = 'Benjamin';
        $user->parent = $parent;

        $this->dm->persist($user);
        $this->dm->flush();

        $users = $this->dm->getRepository(CmsTeamUser::class)->findBy(['nodename' => 'beberlei']);
        $this->assertCount(1, $users);
        $this->assertEquals($user->username, $users['/functional/lsmith/beberlei']->username);
    }

    public function testFindByOrderNonExistentDirectionString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->getRepository(CmsTeamUser::class)->findBy(['nodename' => 'beberlei'], ['username' => 'nowhere']);
    }

    public function testFindByOrderNodename(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->getRepository(CmsTeamUser::class)->findBy(['nodename' => 'beberlei'], ['nodename' => 'asc']);
    }

    public function testFindByOnAssociation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->getRepository(CmsTeamUser::class)->findBy(['parent' => '/foo']);
    }

    public function testFindByOrderAssociation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->getRepository(CmsTeamUser::class)->findBy(['username' => 'beberlei'], ['parent' => 'asc']);
    }

    public function testFindOneBy(): void
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

        $users1 = $this->dm->getRepository(CmsUser::class)->findOneBy(['username' => 'beberlei']);
        $this->assertEquals($user1->username, $users1->username);

        $users2 = $this->dm->getRepository(CmsUser::class)->findOneBy(['username' => 'obama']);
        $this->assertNull($users2);
    }
}
