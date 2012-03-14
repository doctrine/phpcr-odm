<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;

/**
 * @group functional
 */
class DocumentRepositoryTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    public function setUp()
    {
        $this->type = 'Doctrine\Tests\Models\CMS\CmsUser';
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

    public function testLoadMany()
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

        $users = $this->dm->getRepository('Doctrine\Tests\Models\CMS\CmsUser')->findMany(array($user1->id, $user2->id));
        $this->assertSame($user1, $users['/functional/beberlei']);
        $this->assertSame($user2, $users['/functional/lsmith']);

        $this->dm->clear();

        $users = $this->dm->getRepository('Doctrine\Tests\Models\CMS\CmsUser')->findMany(array($user1->id, $user2->id));
        $this->assertEquals($user1->username, $users['/functional/beberlei']->username);
        $this->assertEquals($user2->username, $users['/functional/lsmith']->username);
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

        $users1 = $this->dm->getRepository('Doctrine\Tests\Models\CMS\CmsUser')->findBy(array('username' =>'beberlei'));
        $this->assertCount(1, $users1);
        $this->assertEquals($user1->username, $users1['/functional/beberlei']->username);

        $users2 = $this->dm->getRepository('Doctrine\Tests\Models\CMS\CmsUser')->findBy(array('status' =>'active'));
        $this->assertCount(2, $users2);

        $users3 = $this->dm->getRepository('Doctrine\Tests\Models\CMS\CmsUser')->findBy(array('status' =>'active'), null, 1);
        $this->assertCount(1, $users3);

        $users4 = $this->dm->getRepository('Doctrine\Tests\Models\CMS\CmsUser')->findBy(array('status' =>'active'), array('name'), 2, 0);
        $this->assertEquals('/functional/beberlei', $users4->key());

        $users5 = $this->dm->getRepository('Doctrine\Tests\Models\CMS\CmsUser')->findBy(array('status' =>'active'), array('name'), 2, 1);
        $this->assertEquals('/functional/lsmith', $users5->key());
    }

    public function testFindOneBy()
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

        $users1 = $this->dm->getRepository('Doctrine\Tests\Models\CMS\CmsUser')->findOneBy(array('username' =>'beberlei'));
        $this->assertEquals($user1->username, $users1->username);

        $users2 = $this->dm->getRepository('Doctrine\Tests\Models\CMS\CmsUser')->findOneBy(array('username' =>'obama'));
        $this->assertEquals(null, $users2);
    }
}

