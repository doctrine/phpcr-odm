<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

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
        $user1 = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user1->username = "beberlei";
        $user1->status = "active";
        $user1->name = "Benjamin";
        $user1->id = '/functional/user1';

        $user2 = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user2->username = "lsmith";
        $user2->status = "active";
        $user2->name = "Lukas";
        $user2->id = '/functional/user2';

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();

        $users = $this->dm->getRepository('Doctrine\Tests\Models\CMS\CmsUser')->findMany(array($user1->id, $user2->id));
        $this->assertSame($user1, $users['/functional/user1']);
        $this->assertSame($user2, $users['/functional/user2']);

        $this->dm->clear();

        $users = $this->dm->getRepository('Doctrine\Tests\Models\CMS\CmsUser')->findMany(array($user1->id, $user2->id));
        $this->assertEquals($user1->username, $users['/functional/user1']->username);
        $this->assertEquals($user2->username, $users['/functional/user2']->username);
    }
}
