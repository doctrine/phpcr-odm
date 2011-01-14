<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

/**
 * @group functional
 */
class CollectionTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    private $dm;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
    }

    public function testReplaceArrayWithPersistentCollections()
    {
        $user1 = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user1->username = "beberlei";
        $user1->status = "active";
        $user1->name = "Benjamin";

        $group1 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group1->name = "Admin";

        $user1->addGroup($group1);

        $this->dm->persist($user1);
        $this->dm->persist($group1);
        $this->dm->flush();

        $this->assertType('Doctrine\ODM\PHPCR\PersistentIdsCollection', $user1->groups);
        $this->assertType('Doctrine\ODM\PHPCR\PersistentViewCollection', $group1->users);
    }

    public function testReplaceArrayWithPersistentCollectionsOnMultivalue()
    {
        $group1 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group1->values = array('foo', 'bar');

        $this->dm->persist($group1, '/functional/group');
        $this->dm->flush();

        $this->assertType('Doctrine\ODM\PHPCR\PersistentCollection', $group1->values);
    }
}