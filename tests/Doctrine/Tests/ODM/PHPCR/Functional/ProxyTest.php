<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

use Doctrine\Tests\Models\CMS\CmsUser;

/**
 * @group functional
 */
class ProxyTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->resetFunctionalNode($this->dm);
    }

    public function testProxyProperty()
    {
        $user = new CmsUser();
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getReference(get_class($user), $user->id);

        $this->assertTrue(isset($user->name), "User is not set on demand");
        $this->assertEquals('Dominik', $user->name, "User is not loaded on demand");
    }

    /**
     * @depends testProxyProperty
     */
    public function testProxyUniqueness()
    {
        $user = new CmsUser();
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getReference(get_class($user), $user->id);
        $this->assertEquals('Dominik', $user->name, "User is not loaded on demand");

        $this->assertSame($this->dm->getReference(get_class($user), $user->id), $user, 'Getting the proxy twice results in a copy');
    }

    public function testProxyImplicit()
    {
        $user = new CmsUser();
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';
        $assistant = new CmsUser();
        $assistant->username = 'bimbo';
        $user->child = $assistant;

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(null, $user->id);
        $assistant = $this->dm->find(null, $user->id . '/assistant');

        $this->assertSame($assistant, $user->child);
    }
}
