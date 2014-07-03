<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Hierarchy;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;
use PHPCR\RepositoryInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

/**
 * Test for the Children mapping.
 *
 * @group functional
 */
class ChildrenTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var NodeInterface
     */
    private $node;

    private $type = 'Doctrine\Tests\ODM\PHPCR\Functional\Hierarchy\ChildrenTestObj';

    /**
     * @var TestResetReorderingListener
     */
    private $listener;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);

        $parent = new ChildrenTestObj();
        $parent->id = '/functional/parent';
        $parent->name = 'Parent';
        $this->dm->persist($parent);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function createChildren()
    {
        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child A';
        $child->name = 'Child A';
        $this->dm->persist($child);

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child B';
        $child->name = 'Child B';
        $this->dm->persist($child);

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child C';
        $child->name = 'Child C';
        $this->dm->persist($child);

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child D';
        $child->name = 'Child D';
        $this->dm->persist($child);

        $this->dm->flush();
        $this->dm->clear();
    }

    public function testChildrenCollection()
    {
        $this->createChildren();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $col = $this->dm->getChildren($parent);

        $this->assertCount(4, $col);
        $childA = $col['Child A'];
        $this->assertEquals('Child A', $childA->name);
        $this->assertEquals('Child A', $col->key());

        $col = $this->dm->getChildren($parent, 'Child*');
        $this->assertCount(4, $col);

        $col = $this->dm->getChildren($parent, '*A');
        $this->assertCount(1, $col);
        $this->assertTrue($childA === $col->first());

        $this->dm->clear();

        $this->dm->find($this->type, '/functional/parent/Child D');
        $parent = $this->dm->find($this->type, '/functional/parent');
        $col = $this->dm->getChildren($parent);
        $this->assertEquals('Child A', $col->key());

        $this->dm->clear();

        $this->dm->find($this->type, '/functional/parent/Child D');
        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertEquals('Child A', $parent->allChildren->key());
    }

    public function testSliceChildrenCollection()
    {
        $this->createChildren();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $collection = $parent->allChildren->slice('Child B', 2);
        $this->assertEquals(array('Child B', 'Child C'), array_keys($collection));

        $parent->allChildren->initialize();
        $collection = $parent->allChildren->slice('Child B', 2);
        $this->assertEquals(array('Child B', 'Child C'), array_keys($collection));
    }

    public function testNoChildrenInitOnFlush()
    {
        $this->createChildren();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->dm->flush();

        $this->assertFalse($parent->allChildren->isInitialized());
    }

    public function testLazyLoading()
    {
        $this->createChildren();

        $parent = $this->dm->find($this->type, '/functional/parent');

        // lazy loaded
        $this->assertCount(0, $parent->aChildren->unwrap());
        $this->assertCount(0, $parent->allChildren->unwrap());
        $this->assertCount(1, $parent->aChildren);
        $this->assertCount(4, $parent->allChildren);
        $this->assertFalse($parent->aChildren->contains(new ChildrenTestObj()));
        $this->assertTrue($parent->allChildren->containsKey('Child D'));
        $this->assertEquals('Child B', key($parent->allChildren->slice('Child B', 2)));
        $this->assertCount(2, $parent->allChildren->slice('Child B', 2));
        $this->assertFalse($parent->aChildren->isInitialized());
        $this->assertFalse($parent->allChildren->isInitialized());

        // loaded
        $parent->aChildren[] = new ChildrenTestObj();
        $this->assertCount(2, $parent->aChildren);
        $this->assertTrue($parent->aChildren->isInitialized());

        $parent->allChildren->remove('Child C');
        $this->assertCount(3, $parent->allChildren);
        $this->assertTrue($parent->allChildren->isInitialized());
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

    /**
     * New parent, insert an array of children with an assigned id.
     */
    public function testInsertNewAssignedId()
    {
        $parent = $this->dm->find($this->type, '/functional/parent');
        $new = new ChildrenTestObj();
        $new->id = '/functional/parent/new';

        $children = array();
        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/new/Child Create-1';
        $child->name = 'Child A';
        $children[] = $child;

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/new/Child Create-2';
        $child->name = 'Child B';
        $children[] = $child;

        $new->allChildren = $children;

        $this->dm->persist($new);
        $this->dm->flush();
        $this->dm->clear();

        $new = $this->dm->find($this->type, '/functional/parent/new');
        $this->assertCount(2, $new->allChildren);
    }

    /**
     * Existing parent without children, insert an array of children with an assigned id.
     */
    public function testInsertExistingAssignedId()
    {
        $parent = $this->dm->find($this->type, '/functional/parent');

        $children = array();
        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child Create-1';
        $child->name = 'Child A';
        $children[] = $child;

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child Create-2';
        $child->name = 'Child B';
        $children[] = $child;

        $parent->allChildren = $children;
        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(2, $parent->allChildren);
    }

    /**
     * Existing parent without children, insert children with parent and name strategy.
     */
    public function testInsertExistingNamedChild()
    {
        $parent = $this->dm->find(null, '/functional/parent');

        $child = new ChildrenParentAndNameTestObj();
        $child->name = 'explicit';
        $parent->allChildren->add($child);

        $this->dm->persist($parent);
        $this->dm->flush();

        $this->assertEquals('/functional/parent/explicit', $child->id);

        $this->dm->clear();
        $parent = $this->dm->find(null, '/functional/parent');
        $this->assertCount(1, $parent->allChildren);
    }

    /**
     * New parent, insert children with parent and name strategy
     */
    public function testInsertNewNamedChild()
    {
        $new = new ChildrenTestObj();
        $new->id = '/functional/parent/new';
        $child = new ChildrenParentAndNameTestObj();
        $child->name = 'explicit';

        $new->allChildren->add($child);

        $this->dm->persist($new);
        $this->dm->flush();

        $this->assertEquals('/functional/parent/new/explicit', $child->id);

        $this->dm->clear();
        $parent = $this->dm->find(null, '/functional/parent/new');
        $this->assertCount(1, $parent->allChildren);
    }

    /**
     * Insert children at existing parent with custom id strategy.
     */
    public function testInsertExistingCustomIdStrategy()
    {
        $parent = $this->dm->find($this->type, '/functional/parent');

        $children = array();
        $child = new ChildrenTestObj();
        $child->name = 'ChildA';
        $children[] = $child;

        $child = new ChildrenTestObj();
        $child->name = 'ChildB';
        $children[] = $child;

        $parent->allChildren = $children;
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(2, $parent->allChildren);
    }

    /**
     * Insert child at existing parent, with autoname strategy.
     */
    public function testInsertExistingAutoname()
    {
        /** @var $parent ChildrenTestObj */
        $parent = $this->dm->find(null, '/functional/parent');
        $parent->allChildren->add(new ChildrenAutonameTestObj());

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/parent');
        $this->assertCount(1, $parent->allChildren);
    }

    /**
     * Insert the parent and children at the same time, autoname strategy.
     */
    public function testInsertNewAutoname()
    {
        $new = new ChildrenTestObj();
        $new->id = '/functional/parent/new';
        $new->allChildren->add(new ChildrenAutonameTestObj());

        $this->dm->persist($new);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/parent/new');
        $this->assertCount(1, $parent->allChildren);
    }

    /**
     * A children field must always be a collection/array. It can't be a single document.
     *
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testInsertNoArray()
    {
        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child Create-1';
        $child->name = 'Child A';

        $parent = $this->dm->find($this->type, '/functional/parent');

        $parent->allChildren = $child;
        $this->dm->persist($parent);
        $this->dm->flush();
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testInsertNoObject()
    {
        $parent = $this->dm->find($this->type, '/functional/parent');

        $parent->allChildren = array('This is not an object');
        $this->dm->persist($parent);
        $this->dm->flush();
    }

    public function testRemoveChildParent()
    {
        $this->createChildren();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(4, $parent->allChildren);

        $this->dm->remove($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertNull($parent);
    }

    public function testModifyChildren()
    {
        $this->createChildren();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(4, $parent->allChildren);

        $child = $parent->allChildren->first();
        $child->name = 'New name';

        $parent->allChildren->remove('Child B');
        $parent->allChildren->remove('Child C');

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child E';
        $child->name = 'Child E';

        $parent->allChildren->add($child);
        $this->assertCount(3, $parent->allChildren);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertEquals('New name', $parent->allChildren->first()->name);
        $this->assertCount(3, $parent->allChildren);

        $parent->allChildren->clear();

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(0, $parent->allChildren);

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child F';
        $child->name = 'Child F';

        $parent->allChildren->add($child);

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child G';
        $child->name = 'Child G';

        $parent->allChildren->add($child);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(2, $parent->allChildren);
    }

    public function testReplaceChildren()
    {
        $this->createChildren();

        /** @var $parent ChildrenTestObj */
        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(4, $parent->allChildren);

        $parent->allChildren->remove('Child A');

        $newChild = new ChildrenTestObj();
        $newChild->name = 'Child A';

        $parent->allChildren->add($newChild);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertTrue($parent->allChildren->containsKey('Child A'));
        $this->assertFalse($parent->allChildren->containsKey('0'));
        $this->assertEquals('Child A', $parent->allChildren['Child A']->name);
    }

    /**
     * @depends testModifyChildren
     */
    public function testReorderChildren()
    {
        if (! $this->dm
            ->getPhpcrSession()
            ->getRepository()
            ->getDescriptor(RepositoryInterface::NODE_TYPE_MANAGEMENT_ORDERABLE_CHILD_NODES_SUPPORTED)
        ) {
            $this->markTestSkipped('Reordering of children not supported');
        }

        // run this test again to prepare the database
        $this->testModifyChildren();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(2, $parent->allChildren);

        $data = array(
            'Child G' => $parent->allChildren->last(),
            'Child F' => $parent->allChildren->first(),
        );

        $first = $parent->allChildren->first();
        $parent->allChildren->remove('Child F');
        $parent->allChildren->add($first);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(count($data), $parent->allChildren);
        $this->assertEquals(array_keys($data), $parent->allChildren->getKeys());

        $child1 = new ChildrenTestObj();
        $child1->name = 'Child H';

        $child2 = new ChildrenTestObj();
        $child2->name = 'Child I';

        $child3 = new ChildrenTestObj();
        $child3->name = 'Child J';

        $data = array(
            'Child I' => $child2,
            'Child H' => $child1,
            'Child F' => $parent->allChildren->last(),
            'Child G' => $parent->allChildren->first(),
            'Child J' => $child3
        );

        $parent->allChildren = new ArrayCollection($data);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(count($data), $parent->allChildren);
        $this->assertEquals(array_keys($data), $parent->allChildren->getKeys());

        $last = $parent->allChildren->last();
        $parent->allChildren->remove($parent->allChildren->key());
        $secondlast = $parent->allChildren->last();
        $parent->allChildren->remove($parent->allChildren->key());
        $parent->allChildren->add($last);
        $parent->allChildren->add($secondlast);

        $this->dm->flush();
        $this->dm->clear();

        $keys = array(
            'Child I',
            'Child H',
            'Child F',
            'Child J',
            'Child G',
        );

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(count($keys), $parent->allChildren);
        $this->assertEquals($keys, $parent->allChildren->getKeys());
    }

    public function testReorderChildrenLast()
    {
        if (! $this->dm
            ->getPhpcrSession()
            ->getRepository()
            ->getDescriptor(RepositoryInterface::NODE_TYPE_MANAGEMENT_ORDERABLE_CHILD_NODES_SUPPORTED)
        ) {
            $this->markTestSkipped('Reordering of children not supported');
        }

        $this->createChildren();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(4, $parent->allChildren);

        /** @var $childrenCollection \Doctrine\ODM\PHPCR\ChildrenCollection */
        $childrenCollection = $parent->allChildren;
        $children = $childrenCollection->toArray();

        $childrenCollection->clear();

        $expectedOrder = array('Child A', 'Child D', 'Child C', 'Child B');

        foreach ($expectedOrder as $name) {
            $childrenCollection->set($name, $children[$name]);
        }

        $this->assertEquals($expectedOrder, $parent->allChildren->getKeys());

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(4, $parent->allChildren);
        $this->assertEquals($expectedOrder, $parent->allChildren->getKeys());
    }

    /**
     * Reorder the children but reset the order in the preUpdate event
     * Tests that the previously compute document change set gets overwritten
     *
     * @depends testReorderChildren
     */
    public function testResetReorderChildren()
    {
        $this->createChildren();

        $this->listener = new TestResetReorderingListener();
        $this->dm->getEventManager()->addEventListener(array('preUpdate'), $this->listener);

        /** @var $parent ChildrenTestObj */
        $parent = $this->dm->find($this->type, '/functional/parent');

        $this->assertEquals("Child A", $parent->allChildren->first()->name);

        $parent->allChildren->remove('Child A');

        $newChild = new ChildrenTestObj();
        $newChild->name = 'Child A';

        $parent->allChildren->add($newChild);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');

        $this->assertEquals("Child A", $parent->allChildren->first()->name);

        $this->dm->getEventManager()->removeEventListener(array('preUpdate'), $this->listener);
    }

    /**
     * Rename the nodename of a child
     */
    public function testRenameChildren()
    {
        $parent = $this->dm->find($this->type, '/functional/parent');
        $child = new ChildrenParentAndNameTestObj();
        $child->name = 'original';
        $parent->allChildren->add($child);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $child = $parent->allChildren->first();
        $this->assertEquals('original', $child->name);

        $child->name = 'different';

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(1, $parent->allChildren);
        $this->assertEquals('different', $parent->allChildren->first()->name);
    }

    /**
     * Move a child out of the children collection
     */
    public function testMoveChildren()
    {
        $this->createChildren();
        $parent = $this->dm->find($this->type, '/functional/parent');
        $child = $parent->allChildren->first();

        $this->dm->move($child, '/functional/elsewhere');

        $this->dm->flush();
        $this->dm->clear();

        $child = $this->dm->find(null, '/functional/elsewhere');
        $this->assertInstanceOf($this->type, $child);
        $parent = $this->dm->find(null, '/functional/parent');
        $this->assertInstanceOf($this->type, $parent);
        $this->assertCount(3, $parent->allChildren);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testMoveByAssignment()
    {
        $this->createChildren();

        $other = new ChildrenTestObj();
        $other->id = '/functional/other';
        $this->dm->persist($other);
        $this->dm->flush();
        $this->dm->clear();

        /** @var $parent ChildrenTestObj */
        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(4, $parent->allChildren);

        $other = $this->dm->find($this->type, '/functional/other');
        $other->allChildren->add($parent->allChildren['Child A']);

        $this->dm->flush();
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testMoveByUpdateId()
    {
        $this->createChildren();
        $parent = $this->dm->find($this->type, '/functional/parent');
        $child = $parent->allChildren->first();

        $child->id = '/functional/elsewhere';

        $this->dm->flush();
    }
}

/**
 * @PHPCR\Document(repositoryClass="Doctrine\Tests\ODM\PHPCR\Functional\Hierarchy\ChildrenTestObjRepository")
 */
class ChildrenTestObj
{
    public function __construct()
    {
        $this->aChildren = new ArrayCollection();
        $this->allChildren = new ArrayCollection();
    }

    /** @PHPCR\Id(strategy="repository") */
    public $id;

    /** @PHPCR\String(nullable=true) */
    public $name;

    /** @PHPCR\Children(filter="*A", fetchDepth=1, cascade="persist") */
    public $aChildren;

    /**
    * @var \Doctrine\ODM\PHPCR\ChildrenCollection
    * @PHPCR\Children(fetchDepth=2, cascade="persist")
    */
    public $allChildren;
}

class ChildrenTestObjRepository extends DocumentRepository implements RepositoryIdInterface
{
    /**
     * Generate a document id
     *
     * @param object $document
     * @return string
     */
    public function generateId($document, $parent = null)
    {
        if ($document->id) {
            return $document->id;
        }

        $parent = $parent ? $parent->id : '/functional';
        return $parent.'/'.$document->name;
    }
}

/**
 * @PHPCR\Document()
 */
class ChildrenAutonameTestObj
{
    /** @PHPCR\Id(strategy="auto") */
    public $id;

    /** @PHPCR\ParentDocument() */
    public $parent;
}

/**
 * @PHPCR\Document()
 */
class ChildrenParentAndNameTestObj
{
    /**
     * @PHPCR\ParentDocument
     */
    public $parent;

    /**
     * @PHPCR\Id(strategy="parent")
     */
    public $id;

    /**
     * @PHPCR\Nodename
     */
    public $name;
}

/**
  * @PHPCR\Document()
  */
class ChildrenReferrerTestObj
{
  /** @PHPCR\Id */
  public $id;

  /** @PHPCR\String */
  public $name;

  /** @PHPCR\ReferenceOne(targetDocument="ChildrenReferenceableTestObj", cascade="persist") */
  public $reference;
}

/**
  * @PHPCR\Document(referenceable=true)
  */
class ChildrenReferenceableTestObj
{
  /** @PHPCR\Id */
  public $id;

  /** @PHPCR\String */
  public $name;

  /** @PHPCR\Children(cascade="persist") */
  public $allChildren;
}

class TestResetReorderingListener
{
    public function preUpdate(LifecycleEventArgs $e)
    {
        $document = $e->getObject();
        if ($document instanceof ChildrenTestObj && $document->allChildren->first()->name === 'Child B'){

            /** @var $childrenCollection \Doctrine\ODM\PHPCR\ChildrenCollection */
            $childrenCollection = $document->allChildren;
            $children = $childrenCollection->toArray();

            $childrenCollection->clear();

            $expectedOrder = array('Child A', 'Child B', 'Child C', 'Child D');

            foreach ($expectedOrder as $name) {
                if (!isset($children[$name])) {
                    throw new \PHPUnit_Framework_AssertionFailedError("Missing index '$name' in " . implode(', ', array_keys($children)));
                }
                $childrenCollection->set($name, $children[$name]);
            }

            $document->allChildren = $childrenCollection;
        }
    }
}
