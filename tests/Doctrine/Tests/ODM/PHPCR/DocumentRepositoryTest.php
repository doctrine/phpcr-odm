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

        $constraint = $qb->getConstraint();
        $op1 = $constraint->getOperand1();
        $op2 = $constraint->getOperand2();
        $source = $qb->getSource();
        
        $this->assertEquals('phpcr:class', $op1->getPropertyName());
        $this->assertEquals('stdClass', $op2->getLiteralValue());
        $this->assertEquals('test:node', $source->getNodeTypeName());
    }
}
