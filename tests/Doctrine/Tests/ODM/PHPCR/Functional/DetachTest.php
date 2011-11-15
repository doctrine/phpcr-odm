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
        $user->id = "/functional/".$user->username;

        $this->dm->detach($user);
        
        $this->assertEquals(UnitOfWork::STATE_NEW, $this->dm->getUnitOfWork()->getDocumentState($user));
    }
    
    public function testDetachedKnownObject()
    {
        $user = new CmsUser();
        $user->username = "beberlei";
        $user->name = "Benjamin";
        $user->id = "/functional/".$user->username;

        $this->dm->persist($user);
        $this->dm->flush();
        
        $this->dm->detach($user);
        
        $this->assertEquals(UnitOfWork::STATE_DETACHED, $this->dm->getUnitOfWork()->getDocumentState($user));
    }
}
