<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\DocumentRepository;

/**
 * @group functional
 */
class DocumentRepositoryTest extends PHPCRFunctionalTestCase
{
    public function setUp()
    {
        $session = $this->getMock('PHPCR\SessionInterface');
        $config = new \Doctrine\ODM\PHPCR\Configuration();
        $this->dm = $this->createDocumentManager();
        $this->metadata = new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
    }

    public function testCreateQueryBuilder()
    {
        $rep = new DocumentRepository($this->dm, $this->metadata);
        $qb = $rep->createQueryBuilder();

        $from = $qb->getPart('from');
        
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $from);
    }

    public function testFindBy()
    {
        $rep = new DocumentRepository($this->dm, $this->metadata);
        $res = $rep->findBy(array());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $res);
    }
}
