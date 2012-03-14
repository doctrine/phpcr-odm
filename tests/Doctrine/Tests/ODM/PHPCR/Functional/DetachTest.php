<?php

namespace Doctrine\Tests\ODM\CouchDB\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\ODM\PHPCR\UnitOfWork;

class DetachTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    private $dm;

    private $node;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }
    
    public function testDetachNewObject()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";

        $this->dm->detach($user);

        $this->dm->persist($user);
        $this->dm->flush();

        $check = $this->dm->find('Doctrine\Tests\Models\CMS\CmsUser', $user->id);
        $this->assertEquals('beberlei', $check->username);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDetachedKnownObject()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";

        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->detach($user);
        $this->dm->persist($user);
    }
}
