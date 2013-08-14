<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\Tests\Models\References\RefType2TestObj;
use Doctrine\Tests\Models\References\RefType1TestObj;

/**
 * @group functional
 */
class TargetDocumentTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * @var \PHPCR\NodeInterface
     */
    private $node;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testReferenceManyDifferentTargetDocuments()
    {
        $ref1 = new RefType1TestObj();
        $ref1->id = '/functional/ref1';
        $ref1->name = 'Ref1';
        $ref2 = new RefType2TestObj();
        $ref2->id = '/functional/ref2';
        $ref2->name = 'Ref2';

        $referer = new ReferenceManyObj();
        $referer->id = '/functional/referer';
        $referer->name = 'Referer';
        $referer->references[] = $ref1;
        $referer->references[] = $ref2;

        $this->dm->persist($referer);
        $this->dm->flush();

        $this->dm->clear();
        $referer = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ReferenceManyObj', '/functional/referer');
        $this->assertEquals('Referer', $referer->name);
        $this->assertCount(2, $referer->references);
        $this->assertTrue($referer->references[0] instanceof RefType1TestObj);
        $this->assertTrue($referer->references[1] instanceof RefType2TestObj);
    }

    public function testReferenceOneDifferentTargetDocuments()
    {
        $ref1 = new RefType1TestObj();
        $ref1->id = '/functional/ref1';
        $ref1->name = 'Ref1';
        $ref2 = new RefType2TestObj();
        $ref2->id = '/functional/ref2';
        $ref2->name = 'Ref2';

        $this->dm->persist($ref1);
        $this->dm->persist($ref2);

        $referer1 = new ReferenceOneObj();
        $referer1->id = '/functional/referer1';
        $referer1->reference = $ref1;
        $this->dm->persist($referer1);

        $referer2 = new ReferenceOneObj();
        $referer2->id = '/functional/referer2';
        $referer2->reference = $ref2;
        $this->dm->persist($referer2);

        $this->dm->flush();
        $this->dm->clear();

        $referer = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ReferenceOneObj', '/functional/referer1');
        $this->assertTrue($referer->reference instanceof RefType1TestObj);
        $referer = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ReferenceOneObj', '/functional/referer2');
        $this->assertTrue($referer->reference instanceof RefType2TestObj);
    }
}

/**
 * @PHPCRODM\Document()
 */
class ReferenceManyObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String */
    public $name;
    /** @PHPCRODM\ReferenceMany(cascade="persist") */
    public $references;
}

/**
 * @PHPCRODM\Document()
 */
class ReferenceOneObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String(nullable=true) */
    public $name;
    /** @PHPCRODM\ReferenceOne(cascade="persist") */
    public $reference;
}
