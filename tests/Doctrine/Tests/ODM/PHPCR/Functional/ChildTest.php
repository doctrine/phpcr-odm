<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @group functional
 */
class ChildTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    private $type;
    private $childType;

    private $node;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\ChildTestObj';
        $this->childType = 'Doctrine\Tests\ODM\PHPCR\Functional\ChildChildTestObj';
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testCreate()
    {
        $parent = new ChildTestObj();
        $child = new ChildChildTestObj();
        $parent->name = 'Parent';
        $parent->child = $child;
        $child->name = 'Child';
        $parent->id = '/functional/childtest';

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->getNode('childtest')->hasNode('test'));
        $this->assertEquals($this->node->getNode('childtest')->getNode('test')->getProperty('name')->getString(), 'Child');
    }

    public function testCreateWithoutChild()
    {
        $parent = new ChildTestObj();
        $parent->name = 'Parent';
        $parent->id = '/functional/childtest';

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->node->getNode('childtest')->hasNode('test'));
    }

    public function testCreateAddChildLater()
    {
        $parent = new ChildTestObj();
        $parent->name = 'Parent';
        $parent->id = '/functional/childtest';

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->node->getNode('childtest')->hasNode('test'));

        $parent = $this->dm->find($this->type, '/functional/childtest');
        $parent->child = new ChildChildTestObj();
        $parent->child->name = 'Child';

        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->getNode('childtest')->hasNode('test'));
        $this->assertEquals($this->node->getNode('childtest')->getNode('test')->getProperty('name')->getString(), 'Child');
    }

    public function testUpdate()
    {
        $parent = new ChildTestObj();
        $child = new ChildChildTestObj();
        $parent->name = 'Parent';
        $parent->id = '/functional/childtest';
        $parent->child = $child;
        $child->name = 'Child';

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/childtest');
        $parent->child->name = 'Child changed';

        $this->dm->flush();
        $this->dm->clear();
        $this->assertNotEquals($this->node->getNode('childtest')->getNode('test')->getProperty('name')->getString(), 'Child');
        $this->assertEquals($this->node->getNode('childtest')->getNode('test')->getProperty('name')->getString(), 'Child changed');
    }

    /**
     * Remove the parent node, even when children are modified.
     */
    public function testRemove1()
    {
        $parent = new ChildTestObj();
        $child = new ChildChildTestObj();
        $parent->name = 'Parent';
        $parent->id = '/functional/childtest';
        $parent->child = $child;
        $child->name = 'Child';

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/childtest');
        $parent->child->name = 'Child changed';

        $this->dm->remove($parent);
        $this->dm->flush();
        $this->dm->clear();
        $parent = $this->dm->find($this->type, '/functional/childtest');
        $this->assertNull($parent);
    }

    /**
     * Remove the child, check that parent->child is not set afterwards
     */
    public function testRemove2()
    {
        $parent = new ChildTestObj();
        $child = new ChildChildTestObj();
        $parent->name = 'Parent';
        $parent->id  = '/functional/childtest';
        $parent->child = $child;
        $child->name = 'Child';

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $child = $this->dm->find($this->childType, '/functional/childtest/test');

        $this->dm->remove($child);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/childtest');

        $this->assertNull($parent->child);
        $this->assertTrue($this->node->hasNode('childtest'));
        $this->assertFalse($this->node->getNode('childtest')->hasNode('test'));
    }

    public function testChildSetNull()
    {
        $parent = new ChildTestObj();
        $child = new ChildChildTestObj();
        $parent->name = 'Parent';
        $parent->id = '/functional/childtest';
        $parent->child = $child;
        $child->name = 'Child';

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/childtest');
        $parent->child->name = 'new name';
        $parent->child = null;
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/childtest');
        $this->assertNull($parent->child);
    }

    /* this fails as the newChild is not persisted */
    public function testChildReplace()
    {
        $parent = new ChildTestObj();
        $child = new ChildChildTestObj();
        $parent->name = 'Parent';
        $parent->id = '/functional/childtest';
        $parent->child = $child;
        $child->name = 'Child';

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/childtest');
        $newChild = new ChildChildTestObj();
        $newChild->name = 'new name';
        $parent->child = $newChild;

        $this->setExpectedException('Doctrine\ODM\PHPCR\PHPCRException');

        $this->dm->flush();
    }

    public function testModificationAfterPersist()
    {
        $parent = new ChildTestObj();
        $child = new ChildChildTestObj();

        $parent->id = '/functional/childtest';
        $this->dm->persist($parent);
        $parent->name = 'Parent';
        $parent->child = $child;
        $child->name = 'Child';

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/childtest');
        $this->assertNotNull($parent->child);
        $this->assertEquals('Child', $parent->child->name);

        $parent->child->name = 'Changed';

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/childtest');

        $this->assertNotNull($parent->child);
        $this->assertEquals('Changed', $parent->child->name);
    }

    public function testChildOfReference()
    {
        $referrerTestObj = new ChildReferrerTestObj();
        $referrerTestObj->id = "/functional/referrerTestObj";
        $referrerTestObj->name = "referrerTestObj";

        $refererenceableTestObj = new ChildReferenceableTestObj();
        $refererenceableTestObj->id = "/functional/referenceableTestObj";
        $refererenceableTestObj->name = "referenceableTestObj";
        $referrerTestObj->reference = $refererenceableTestObj;

        $this->dm->persist($referrerTestObj);

        $ChildTestObj = new ChildTestObj();
        $ChildTestObj->id = "/functional/referenceableTestObj/test";
        $ChildTestObj->name= "childTestObj";

        $this->dm->persist($ChildTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find(null, "/functional/referrerTestObj");

        $this->assertEquals($referrer->reference->aChild->name, "childTestObj");
    }
}

/**
 * @PHPCRODM\Document()
 */
class ChildChildTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\String */
    public $name;
}

/**
 * @PHPCRODM\Document()
 */
class ChildTestObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\String */
    public $name;
    /** @PHPCRODM\Child(name="test") */
    public $child;
}

/**
  * @PHPCRODM\Document()
  */
class ChildReferrerTestObj
{
  /** @PHPCRODM\Id */
  public $id;

  /** @PHPCRODM\String */
  public $name;

  /** @PHPCRODM\ReferenceOne(targetDocument="ChildReferenceableTestObj") */
  public $reference;
}

/**
  * @PHPCRODM\Document(referenceable=true)
  */
class ChildReferenceableTestObj
{
  /** @PHPCRODM\Id */
  public $id;

  /** @PHPCRODM\String */
  public $name;

  /** @PHPCRODM\Child(name="test") */
  public $aChild;
}
