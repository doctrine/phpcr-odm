<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

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
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\TestObj';
        $this->childType = 'Doctrine\Tests\ODM\PHPCR\Functional\ChildTestObj';
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testCreate()
    {
        $parent = new TestObj();
        $child = new ChildTestObj();
        $parent->name = 'Parent';
        $parent->child = $child;
        $child->name = 'Child';

        $this->dm->persist($parent, '/functional/childtest');
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($this->node->getNode('childtest')->hasNode('test'));
        $this->assertEquals($this->node->getNode('childtest')->getNode('test')->getProperty('name')->getString(), 'Child');
    }

    public function testCreateWithoutChild()
    {
        $parent = new TestObj();
        $parent->name = 'Parent';

        $this->dm->persist($parent, '/functional/childtest');
        $this->dm->flush();
        $this->dm->clear();

        $this->assertFalse($this->node->getNode('childtest')->hasNode('test'));
    }

    public function testUpdate()
    {
        $parent = new TestObj();
        $child = new ChildTestObj();
        $parent->name = 'Parent';
        $parent->child = $child;
        $child->name = 'Child';

        $this->dm->persist($parent, '/functional/childtest');
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/childtest');
        $parent->child->name = 'Child changed';

        $this->dm->persist($parent, $parent->path); 
        $this->dm->flush();
        $this->dm->clear();
        $this->assertNotEquals($this->node->getNode('childtest')->getNode('test')->getProperty('name')->getString(), 'Child');
        $this->assertEquals($this->node->getNode('childtest')->getNode('test')->getProperty('name')->getString(), 'Child changed');
    }

    public function testRemove1()
    {
        $parent = new TestObj();
        $child = new ChildTestObj();
        $parent->name = 'Parent';
        $parent->child = $child;
        $child->name = 'Child';

        $this->dm->persist($parent, '/functional/childtest');
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/childtest');
        //$parent->child->name = 'Child changed';

        $this->dm->remove($parent); 
        $this->dm->flush();
        $this->dm->clear();
        $parent = $this->dm->find($this->type, '/functional/childtest');
        $this->assertNull($parent);
    }

    public function testRemove2()
    {
        $parent = new TestObj();
        $child = new ChildTestObj();
        $parent->name = 'Parent';
        $parent->child = $child;
        $child->name = 'Child';

        $this->dm->persist($parent, '/functional/childtest');
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
        $parent = new TestObj();
        $child = new ChildTestObj();
        $parent->name = 'Parent';
        $parent->child = $child;
        $child->name = 'Child';

        $this->dm->persist($parent, '/functional/childtest');
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
        $parent = new TestObj();
        $child = new ChildTestObj();
        $parent->name = 'Parent';
        $parent->child = $child;
        $child->name = 'Child';

        $this->dm->persist($parent, '/functional/childtest');
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/childtest');
        $newChild = new ChildTestObj();
        $newChild->name = 'new name';
        $parent->child = $newChild;
        $this->dm->flush();
        $this->dm->clear();
      
        $parent = $this->dm->find($this->type, '/functional/childtest');
        $this->assertEquals($parent->child->name, 'new name');
    }
}


/**
 * @Document(alias="childTestObj")
 */
class ChildTestObj
{
    /** @Path */
    public $path;
    /** @Node */
    public $node;
    /** @String */
    public $name;
}
/**
 * @Document(alias="testObj")
 */
class TestObj
{
    /** @Path */
    public $path;
    /** @Node */
    public $node;
    /** @String */
    public $name;
    /** @Child(name="test") */
    public $child;
}
