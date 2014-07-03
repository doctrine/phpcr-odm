<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsTeamUser;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\References\UuidTestObj;
use Doctrine\Tests\Models\References\UuidTestTwoUuidFieldsObj;

/**
 * @group functional
 */
class FlushTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
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

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\Models\CMS\CmsUser';
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->resetFunctionalNode($this->dm);
    }

    public function testFlushSingleDocument()
    {
        $user1 = new CmsUser();
        $user1->username = 'romanb';
        $user2 = new CmsUser();
        $user2->username = 'jwage';
        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();
        $this->dm->clear();

        $user1 = $this->dm->find($this->type, '/functional/romanb');
        $this->assertEquals('romanb', $user1->username);

        $user2 = $this->dm->find($this->type, '/functional/jwage');
        $this->assertEquals('jwage', $user2->username);

        $user1->username = 'changed';
        $user2->username = 'changed';
        $this->dm->flush($user1);
        $this->dm->clear();

        $check = $this->dm->find($this->type, '/functional/romanb');
        $this->assertEquals('changed', $check->username);

        $check = $this->dm->find($this->type, '/functional/jwage');
        $this->assertEquals('jwage', $check->username);
    }

    public function testFlushSingleDocumentThenFlush()
    {
        $user1 = new CmsUser();
        $user1->username = 'romanb';
        $user2 = new CmsUser();
        $user2->username = 'jwage';
        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();

        $user1->username = 'changed';
        $user2->username = 'changed';
        $this->dm->flush($user1);

        $check = $this->dm->find($this->type, '/functional/romanb');
        $this->assertEquals('changed', $check->username);

        $this->dm->flush();

        $check = $this->dm->find($this->type, '/functional/jwage');
        $this->assertEquals('changed', $check->username);
    }

    public function testFlushSingleDocumentWithParent()
    {
        $user1 = new CmsUser();
        $user1->username = 'romanb';
        $user2 = new CmsTeamUser();
        $user2->username = 'jwage';
        $user2->parent = $user1;
        $user3 = new CmsTeamUser();
        $user3->username = 'beberlei';
        $user3->parent = $user2;
        $this->dm->persist($user3);
        $this->dm->flush($user3);

        $user1 = $this->dm->find($this->type, '/functional/romanb');
        $this->assertEquals('romanb', $user1->username);

        $user2 = $this->dm->find($this->type, '/functional/romanb/jwage');
        $this->assertEquals('jwage', $user2->username);

        $user3 = $this->dm->find($this->type, '/functional/romanb/jwage/beberlei');
        $this->assertEquals('beberlei', $user3->username);

        $user1->username = 'changed';
        $user2->username = 'changed';
        $user3->username = 'changed';
        $this->dm->flush($user3);

        $user1 = $this->dm->find($this->type, '/functional/romanb');
        $this->assertEquals('changed', $user1->username);

        $user2 = $this->dm->find($this->type, '/functional/romanb/jwage');
        $this->assertEquals('changed', $user2->username);

        $user3 = $this->dm->find($this->type, '/functional/romanb/jwage/beberlei');
        $this->assertEquals('changed', $user3->username);
    }

    public function testFlushSingleManagedDocument()
    {
        $user = new CmsUser();
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';

        $this->dm->persist($user);
        $this->dm->flush();

        $user->status = 'administrator';
        $this->dm->flush($user);
        $this->dm->clear();

        $user = $this->dm->find(get_class($user), $user->id);
        $this->assertEquals('administrator', $user->status);
    }

    public function testFlushManyExplicitDocuments()
    {
        $userA = new CmsUser('userA');
        $userA->username = 'userA';
        $userB = new CmsUser('userB');
        $userB->username = 'userB';
        $userC = new CmsUser('userC');
        $userC->username = 'userC';

        $this->dm->persist($userA);
        $this->dm->persist($userB);
        $this->dm->persist($userC);

        $this->dm->flush(array($userA, $userB, $userC));

        $this->assertNotNull($userA->id);
        $this->assertNotNull($userB->id);
        $this->assertNotNull($userC->id);
    }

    public function testFlushSingleUnmanagedDocument()
    {
        $user = new CmsUser();
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';

        $this->setExpectedException('\Doctrine\ODM\PHPCR\Exception\InvalidArgumentException');
        $this->dm->flush($user);
    }

    public function testFlushSingleAndNewDocument()
    {
        $user = new CmsUser();
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';

        $this->dm->persist($user);
        $this->dm->flush();

        $otherUser = new CmsUser;
        $otherUser->name = 'Dominik2';
        $otherUser->username = 'domnikl2';
        $otherUser->status = 'developer';

        $user->status = 'administrator';

        $this->dm->persist($otherUser);
        $this->dm->flush($user);

        $this->assertTrue($this->dm->contains($otherUser), "Other user is not contained in DocumentManager");
        $this->assertTrue($otherUser->id != null, "other user has no id");
    }

    public function testFlushAndCascadePersist()
    {
        $user = new CmsUser();
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';

        $this->dm->persist($user);
        $this->dm->flush();

        $address = new CmsAddress();
        $address->city = "Springfield";
        $address->zip = "12354";
        $address->country = "Germany";
        $address->user = $user;
        $user->address = $address;

        $this->dm->flush($user);

        $this->assertTrue($this->dm->contains($address), "Address is not contained in DocumentManager");
        $this->assertTrue($address->id != null, "address user has no id");
    }

    public function testProxyIsIgnored()
    {
        $user = new CmsUser();
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getReference(get_class($user), $user->id);

        $otherUser = new CmsUser;
        $otherUser->name = 'Dominik2';
        $otherUser->username = 'domnikl2';
        $otherUser->status = 'developer';

        $this->dm->persist($otherUser);
        $this->dm->flush($user);

        $this->assertTrue($this->dm->contains($otherUser), "Other user is contained in DocumentManager");
        $this->assertTrue($otherUser->id != null, "other user has no id");
    }

    public function testUuidIsSet()
    {
        $uuidObj = new UuidTestObj;
        $uuidObj->id = '/functional/uuidObj';
        $this->dm->persist($uuidObj);
        $this->dm->flush();
        $this->assertNotNull($uuidObj->uuid1);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testUuidFieldOnlySetOnce()
    {
        $uuidObj = new UuidTestTwoUuidFieldsObj;
        $uuidObj->id = '/functional/uuidObj';
        $this->dm->persist($uuidObj);
        $this->dm->flush();
    }

    public function testRepeatedFlush()
    {
        $user1 = new CmsUser();
        $user1->username = 'romanb';
        $user2 = new CmsTeamUser();
        $user2->username = 'jwage';
        $user2->parent = $user1;
        $user3 = new CmsTeamUser();
        $user3->username = 'beberlei';
        $user3->parent = $user2;

        $group = new CmsGroup();
        $group->id = '/functional/group';
        $group->setName('foo');
        $group->addUser($user1);
        $group->addUser($user2);
        $group->addUser($user3);
        $this->dm->persist($group);
        $this->assertCount(3, $group->getUsers());
        $this->dm->flush();

        $user4 = new CmsTeamUser();
        $user4->username = 'ocranimus';
        $user4->parent = $user1;
        $group->addUser($user4);
        $this->assertCount(4, $group->getUsers());
        $this->dm->flush();

        $this->dm->getPhpcrSession()->removeItem($user2->id);
        $this->dm->getPhpcrSession()->save();
        $this->dm->flush();
        $this->assertInstanceOf('\PHPCR\NodeInterface', $user1->node);

        $this->assertCount(4, $group->getUsers());
        $this->dm->clear();

        $group = $this->dm->find(null, '/functional/group');
        $group->getUsers()->first();
        $this->assertCount(2, $group->getUsers());
        $this->assertInstanceOf('\PHPCR\NodeInterface', $group->getUsers()->first()->node);
    }
}
