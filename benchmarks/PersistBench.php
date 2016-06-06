<?php

namespace Doctrine\Benchmarks\ODM\PHPCR;

use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\References\ParentNoNodeNameTestObj;
use Doctrine\Tests\Models\References\ParentTestObj;
use Doctrine\Tests\Models\Translation\Comment;
use Doctrine\Tests\ODM\PHPCR\Functional\TestUser;

/**
 * @BeforeMethods({"setUp"})
 * @Iterations(10)
 * @OutputTimeUnit("milliseconds", precision=2)
 */
class PersistBench extends PHPCRFunctionalTestCase
{
    private $root;

    public function setUp()
    {
        $this->documentManager = $this->createDocumentManager();
        $this->resetFunctionalNode($this->documentManager);
        $this->root = $this->documentManager->find(null, '/functional');
    }

    public function benchPersistTwo()
    {
        $user1 = new CmsUser();
        $user1->username = 'dantleech';
        $address = new CmsAddress();
        $address->city = "Springfield";
        $address->zip = "12354";
        $address->country = "Germany";
        $user1->address = $address;

        $this->documentManager->persist($user1);
        $this->documentManager->persist($address);
        $this->documentManager->flush();
    }

    public function benchPersistMany()
    {
        $parent1 = new ParentTestObj();
        $parent1->nodename = "root1";
        $parent1->name = "root1";
        $parent1->setParentDocument($this->root);

        $parent2 = new ParentTestObj();
        $parent2->name = "/root2";
        $parent2->nodename = "root2";
        $parent2->setParentDocument($this->root);

        $child = new ParentNoNodeNameTestObj();
        $child->setParentDocument($parent1);
        $child->name = "child";

        $c1 = new Comment();
        $c1->name = 'c1';
        $c1->parent = $this->root;
        $c1->setText('deutsch');

        $group1 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group1->name = "Test!";
        $group1->id = '/functional/group1';

        $group2 = new \Doctrine\Tests\Models\CMS\CmsGroup();
        $group2->name = "Test!";
        $group2->id = '/functional/group2';

        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";
        $user->addGroup($group1);
        $user->addGroup($group2);

        $this->documentManager->persist($parent1);
        $this->documentManager->persist($parent2);
        $this->documentManager->persist($child);
        $this->documentManager->persist($c1);
        $this->documentManager->persist($user);
        $this->documentManager->persist($group1);
        $this->documentManager->persist($group2);
        $this->documentManager->flush();
    }
}
