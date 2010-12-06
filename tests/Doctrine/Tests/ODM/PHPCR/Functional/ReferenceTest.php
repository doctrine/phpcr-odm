<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

class ReferenceTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    public function testReferenceUsesIdentityMap()
    {
        $dm = $this->createDocumentManager();
        $user1 = $dm->getReference('Doctrine\Tests\Models\CMS\CmsUser', 1);
        $user2 = $dm->getReference('Doctrine\Tests\Models\CMS\CmsUser', 1);

        $this->assertSame($user1, $user2);
    }

    public function testInitializeUnknownReferenceThrowsException()
    {
        $dm = $this->createDocumentManager();
        $user1 = $dm->getReference('Doctrine\Tests\Models\CMS\CmsUser', 1);

        $this->setExpectedException('Doctrine\ODM\PHPCR\DocumentNotFoundException');
        $user1->getUsername();
    }

    public function testLazyLoadReference()
    {
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";
        $user->status = "active";

        $dm = $this->createDocumentManager();
        $dm->persist($user);
        $dm->flush();
        $dm->clear();

        $lazyUser = $dm->getReference('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        $this->assertFalse($lazyUser->__isInitialized__);

        // Trigger lazyload
        $lazyUser->getUsername();

        $this->assertTrue($lazyUser->__isInitialized__);
    }
}