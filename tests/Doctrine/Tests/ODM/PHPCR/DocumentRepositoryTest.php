<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode as QBConstants;

/**
 * @group functional
 */
class DocumentRepositoryTest extends PHPCRFunctionalTestCase
{
    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->metadata = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
    }

    public function testCreateQueryBuilder()
    {
        $rep = new DocumentRepository($this->dm, $this->metadata);
        $qb = $rep->createQueryBuilder('a');

        $from = $qb->getChildOfType(QBConstants::NT_FROM);
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Query\Builder\From', $from);
        $source = $from->getChildOfType(QBConstants::NT_SOURCE);
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Query\Builder\SourceDocument', $source);
        
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $source->getDocumentFqn());
    }

    public function testFindBy()
    {
        $rep = new DocumentRepository($this->dm, $this->metadata);
        $res = $rep->findBy(array());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $res);
    }
}
