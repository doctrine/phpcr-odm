<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

class ReferenceTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
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
        $user = $this->node->addNode('CmsUser');
        $user->setProperty('username', 'lsmith');
        $user->setProperty('_doctrine_alias', 'cms_user');

        $session->save();
    }

    public function testReferenceUsesIdentityMap()
    {
        $user1 = $this->dm->getReference($this->type, '/functional/CmsUser');
        $user2 = $this->dm->getReference($this->type, '/functional/CmsUser');

        $this->assertSame($user1, $user2);
    }

    public function testInitializeUnknownReferenceThrowsException()
    {
        $user1 = $this->dm->getReference($this->type, '/functional/reference');

        $this->setExpectedException('PHPCR\PathNotFoundException');
        $user1->getUsername();
    }

    public function testLazyLoadReference()
    {
        $user = new \Doctrine\Tests\Models\CMS\CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";
        $user->status = "active";

        $dm = $this->createDocumentManager();
        $dm->persist($user, '/functional/benjamin');
        $dm->flush();
        $dm->clear();

        $lazyUser = $dm->getReference('Doctrine\Tests\Models\CMS\CmsUser', '/functional/benjamin');
        $this->assertFalse($lazyUser->__isInitialized__);

        // Trigger lazyload
        $lazyUser->getUsername();

        $this->assertTrue($lazyUser->__isInitialized__);
    }
}