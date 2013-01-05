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
        $this->metadata = new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata('stdClass');
        $this->metadata->setNodeType('test:node');
    }

    public function testCreateQueryBuilder()
    {
        $rep = new DocumentRepository($this->dm, $this->metadata);
        $qb = $rep->createQueryBuilder();

        $comparison = $qb->getPart('where');
        $op1 = $comparison->getField();
        $op2 = $comparison->getValue();
        $source = $qb->getPart('from');
        
        $this->assertEquals('phpcr:class', $op1);
        $this->assertEquals('stdClass', $op2->getValue());
        $this->assertEquals('test:node', $source->getNodeTypeName());
    }
}
