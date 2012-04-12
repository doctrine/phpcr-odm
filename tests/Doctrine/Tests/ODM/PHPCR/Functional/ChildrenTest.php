<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

use Doctrine\Common\Collections\ArrayCollection;

use PHPCR\UnsupportedRepositoryOperationException;

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

        $this->assertCount(4, $col);
        $childA = $col['child-a'];
        $this->assertEquals('Child A', $childA->name);

        $col = $this->dm->getChildren($parent, 'child*');
        $this->assertCount(4, $col);

        $col = $this->dm->getChildren($parent, '*a');
        $this->assertCount(1, $col);
        $this->assertTrue($childA === $col->first());
    }

    public function testNoChildrenInitOnFlush()
    {
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $this->dm->flush();

        $this->assertFalse($parent->allChildren->isInitialized());
    }

    public function testAnnotation()
    {
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        // lazy loaded
        $this->assertNull($parent->aChildren->unwrap());
        $this->assertNull($parent->allChildren->unwrap());
        // loaded
        $this->assertCount(1, $parent->aChildren);
        $this->assertCount(4, $parent->allChildren);
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

        $this->assertCount(1, $referrer->reference->allChildren);
        $this->assertEquals("childrenTestObj", $referrer->reference->allChildren->first()->name);
    }

    public function testCreateChildren()
    {
        $children = array();
        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/child-a/child-create-1';
        $child->name = 'Child A';
        $children[] = $child;

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/child-a/child-create-2';
        $child->name = 'Child B';
        $children[] = $child;

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent/child-a');
        $this->assertCount(0, $parent->allChildren);

        $parent->allChildren = $children;
        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent/child-a');
        $this->assertCount(2, $parent->allChildren);
    }

    public function testRemoveChildParent()
    {
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $this->assertCount(4, $parent->allChildren);

        $this->dm->remove($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $this->assertNull($parent);
    }

    public function testModifyChildren()
    {
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $this->assertCount(4, $parent->allChildren);

        $child = $parent->allChildren->first();
        $child->name = 'New name';

        $parent->allChildren->remove('child-b');
        $parent->allChildren->remove('child-c');

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/child-e';
        $child->name = 'Child E';

        $parent->allChildren->add($child);
        $this->assertCount(3, $parent->allChildren);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $this->assertEquals('New name', $parent->allChildren->first()->name);
        $this->assertCount(3, $parent->allChildren);

        $parent->allChildren->clear();

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $this->assertCount(0, $parent->allChildren);

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/child-f';
        $child->name = 'Child F';

        $parent->allChildren->add($child);

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/child-g';
        $child->name = 'Child G';

        $parent->allChildren->add($child);

        $this->dm->flush();
        $this->dm->clear();
    }

    public function testReorderChildren()
    {
        $this->testModifyChildren();

        try {
            $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
            $this->assertCount(2, $parent->allChildren);

            $data = array(
                'child-g' => $parent->allChildren->last(),
                'child-f' => $parent->allChildren->first(),
            );

            $parent->allChildren = new ArrayCollection($data);

            $this->dm->flush();
            $this->dm->clear();

            $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
            $this->assertCount(count($data), $parent->allChildren);
            $this->assertEquals(array_keys($data), $parent->allChildren->getKeys());

            $child1 = new ChildrenTestObj();
            $child1->name = 'Child H';

            $child2 = new ChildrenTestObj();
            $child2->name = 'Child I';

            $child3 = new ChildrenTestObj();
            $child3->name = 'Child J';

            $data = array(
                'child-i' => $child2,
                'child-h' => $child1,
                'child-f' => $parent->allChildren->last(),
                'child-g' => $parent->allChildren->first(),
                'child-j' => $child3
            );

            $parent->allChildren = new ArrayCollection($data);

            $this->dm->flush();
            $this->dm->clear();

            $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
            $this->assertCount(count($data), $parent->allChildren);
            $this->assertEquals(array_keys($data), $parent->allChildren->getKeys());
        } catch (UnsupportedRepositoryOperationException $e) {
            $this->markTestSkipped('Reordering of children not supported');
        }
    }
}

/**
  * @PHPCRODM\Document()
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
  * @PHPCRODM\Document()
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
  * @PHPCRODM\Document(referenceable=true)
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
