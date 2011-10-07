<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
/**
 * @group functional
 */
class ChildrenTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{

  /**
     * @var DocumentManager
     */
    private $dm;
    private $node;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);

        $parent = new ChildrenTestObj();
        $parent->id = '/functional/parent';
        $parent->name = 'Parent';
        $this->dm->persist($parent);

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/child-a';
        $child->name = 'Child A';
        $this->dm->persist($child);

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/child-b';
        $child->name = 'Child B';
        $this->dm->persist($child);

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/child-c';
        $child->name = 'Child C';
        $this->dm->persist($child);

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/child-d';
        $child->name = 'Child D';
        $this->dm->persist($child);

        $this->dm->flush();
        $this->dm->clear();
    }

    public function testChildrenCollection()
    {
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $col = $this->dm->getChildren($parent);

        $this->assertEquals(4, count($col));
        $childA = $col['child-a'];
        $this->assertEquals('Child A', $childA->name);

        $col = $this->dm->getChildren($parent, 'child*');
        $this->assertEquals(4, count($col));

        $col = $this->dm->getChildren($parent, '*a');
        $this->assertEquals(1, count($col));
        $this->assertTrue($childA === $col->first());
    }

    public function testAnnotation()
    {
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        // lazy loaded
        $this->assertNull($parent->aChildren->unwrap());
        $this->assertNull($parent->allChildren->unwrap());
        // loaded
        $this->assertEquals(1, count($parent->aChildren));
        $this->assertEquals(4, count($parent->allChildren));
    }

    public function testChildrenOfReference()
    {
        $referrerTestObj = new ChildrenReferrerTestObj();
        $referrerTestObj->id = "/functional/referrerTestObj";
        $referrerTestObj->name = "referrerTestObj";

        $refererenceableTestObj = new ChildrenReferenceableTestObj();
        $refererenceableTestObj->id = "/functional/referenceableTestObj";
        $refererenceableTestObj->name = "referenceableTestObj";
        $referrerTestObj->reference = $refererenceableTestObj;

        $this->dm->persist($referrerTestObj);

        $ChildrenTestObj = new ChildrenTestObj();
        $ChildrenTestObj->id = "/functional/referenceableTestObj/childrenTestObj";
        $ChildrenTestObj->name= "childrenTestObj";

        $this->dm->persist($ChildrenTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find(null, "/functional/referrerTestObj");

        $this->assertEquals(count($referrer->reference->allChildren), 1);
        $this->assertEquals($referrer->reference->allChildren->first()->name, "childrenTestObj");

    }
}

/**
  * @PHPCRODM\Document(alias="childrenTest")
  */
class ChildrenTestObj
{
  /** @PHPCRODM\Id */
  public $id;

  /** @PHPCRODM\String */
  public $name;

  /** @PHPCRODM\Children(filter="*a") */
  public $aChildren;

  /** @PHPCRODM\Children */
  public $allChildren;
}

/**
  * @PHPCRODM\Document(alias="Referrer")
  */
class ChildrenReferrerTestObj
{
  /** @PHPCRODM\Id */
  public $id;

  /** @PHPCRODM\String */
  public $name;

  /** @PHPCRODM\ReferenceOne(targetDocument="ChildrenReferenceableTestObj") */
  public $reference;
}

/**
  * @PHPCRODM\Document(alias="ChildrenReferenceableTestObj", referenceable=true)
  */
class ChildrenReferenceableTestObj 
{
  /** @PHPCRODM\Id */
  public $id;

  /** @PHPCRODM\String */
  public $name;

  /** @PHPCRODM\Children */
  public $allChildren;
}
