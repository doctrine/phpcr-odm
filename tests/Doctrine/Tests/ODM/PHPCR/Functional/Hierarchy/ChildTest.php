<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Hierarchy;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Id\IdException;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\PHPCRException;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;

/**
 * Test for the Child mapping.
 *
 * @group functional
 */
class ChildTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * Class name of the document class
     *
     * @var string
     */
    private $type = ChildTestObj::class;

    /**
     * Class name of the child document class
     *
     * @var string
     */
    private $childType = ChildChildTestObj::class;

    /**
     * @var NodeInterface
     */
    private $node;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testInsertWithoutChild()
    {
        $parent = new ChildTestObj();
        $parent->name = 'Parent';
        $parent->id = '/functional/childtest';

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->node->getNode('childtest')->hasNode('test'));
    }

    public function testInsertWithUnnamedChild()
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

    /**
     * @depends testInsertWithUnnamedChild
     */
    public function testProxyForChildIsUsed()
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

        $doc = $this->dm->find(null, '/functional/childtest');
        $this->assertInstanceOf($this->type, $doc);
        $this->assertInstanceOf(Proxy::class, $doc->child);
    }

    public function testInsertAddUnnamedChildLater()
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

    public function testCreateConflictingName()
    {
        $parent = new ChildTestObj();
        $child = new ChildChildTestObj();
        $parent->name = 'Parent';
        $parent->child = $child;
        $child->name = 'Child';
        $child->nodename = 'different';
        $parent->id = '/functional/childtest';

        $this->dm->persist($parent);

        $this->expectException(IdException::class);
        $this->dm->flush();
    }

    /**
     * On creation, a conflicting child name is not ok. On update it is
     * allowed, so that a node can be moved away.
     */
    public function testMoveAwayChild()
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

        $parent = $this->dm->find(null, '/functional/childtest');
        $this->assertInstanceOf($this->type, $parent);
        $this->assertInstanceOf($this->childType, $parent->child);

        $parent->child->nodename = 'different';
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/childtest');
        $this->assertInstanceOf($this->type, $parent);
        $this->assertNull($parent->child);
    }

    public function testInsertArray()
    {
        $parent = new ChildTestObj();
        $child = new ChildChildTestObj();
        $parent->name = 'Parent';
        $parent->child = [$child];
        $child->name = 'Child';
        $parent->id = '/functional/childtest';

        $this->dm->persist($parent);

        $this->expectException(PHPCRException::class);
        $this->dm->flush();
    }

    public function testInsertNoObject()
    {
        $parent = new ChildTestObj();
        $child = new ChildChildTestObj();
        $parent->name = 'Parent';
        $parent->child = 'This is not an object';
        $child->name = 'Child';
        $parent->id = '/functional/childtest';

        $this->dm->persist($parent);

        $this->expectException(PHPCRException::class);
        $this->dm->flush();
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
        $parent->id = '/functional/childtest';
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

    /**
     * Remove the parent node of multiple child level
     */
    public function testRemove3()
    {
        $parent = new ChildTestObj();
        $parent->name = 'Parent';
        $parent->id = '/functional/childtest';
        // Lv1
        $childLv1 = new ChildReferenceableTestObj();
        $childLv1->name = 'Child-Lv1';
        $parent->child = $childLv1;
        // Lv2
        $childLv2 = new ChildChildTestObj();
        $childLv2->name = 'Child-Lv2';
        $childLv1->aChild = $childLv2;

        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/childtest');
        $this->assertTrue($this->node->hasNode('childtest'));
        $this->assertTrue($this->node->getNode('childtest')->hasNode('test'));
        $this->assertTrue($this->node->getNode('childtest')->getNode('test')->hasNode('test'));

        $this->dm->remove($parent);
        $this->dm->flush();
        $this->dm->clear();
        $parent = $this->dm->find($this->type, '/functional/childtest');
        $this->assertNull($parent);
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

    public function testMoveByAssignment()
    {
        $original = new ChildTestObj();
        $child = new ChildChildTestObj();
        $original->name = 'Parent';
        $original->id = '/functional/original';
        $original->child = $child;
        $child->name = 'Child';

        $this->dm->persist($original);

        $other = new ChildTestObj();
        $other->name = 'newparent';
        $other->id = '/functional/newlocation';
        $this->dm->persist($other);

        $this->dm->flush();
        $this->dm->clear();

        $original = $this->dm->find($this->type, '/functional/original');
        $other = $this->dm->find($this->type, '/functional/newlocation');

        $other->child = $original->child;

        $this->expectException(PHPCRException::class);
        $this->dm->flush();
    }

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

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/childtest');
        $this->assertEquals('new name', $parent->child->name);
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
        $referrerTestObj->id = '/functional/referrerTestObj';
        $referrerTestObj->name = 'referrerTestObj';

        $refererenceableTestObj = new ChildReferenceableTestObj();
        $refererenceableTestObj->id = '/functional/referenceableTestObj';
        $refererenceableTestObj->name = 'referenceableTestObj';
        $referrerTestObj->reference = $refererenceableTestObj;

        $this->dm->persist($referrerTestObj);

        $ChildTestObj = new ChildTestObj();
        $ChildTestObj->id = '/functional/referenceableTestObj/test';
        $ChildTestObj->name = 'childTestObj';

        $this->dm->persist($ChildTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find(null, '/functional/referrerTestObj');

        $this->assertEquals($referrer->reference->aChild->name, 'childTestObj');
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

    /** @PHPCRODM\Field(type="string") */
    public $name;

    /** @PHPCRODM\Nodename */
    public $nodename;
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

    /** @PHPCRODM\Field(type="string") */
    public $name;

    /** @PHPCRODM\Child(nodeName="test", cascade="persist") */
    public $child;
}

/**
 * @PHPCRODM\Document()
 */
class ChildReferrerTestObj
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Field(type="string") */
    public $name;

    /** @PHPCRODM\ReferenceOne(targetDocument="ChildReferenceableTestObj", cascade="persist") */
    public $reference;
}

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class ChildReferenceableTestObj
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Field(type="string") */
    public $name;

    /** @PHPCRODM\Child(nodeName="test", cascade="persist") */
    public $aChild;
}
