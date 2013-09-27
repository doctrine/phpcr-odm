<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface,
    Doctrine\ODM\PHPCR\DocumentRepository,
    Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\ChildrenCollection;
use PHPCR\RepositoryInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

use PHPCR\UnsupportedRepositoryOperationException;

/**
 * @group functional
 */
class ChildrenTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
  /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * @var \PHPCR\NodeInterface
     */
    private $node;

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
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
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

        $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent/Child D');
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $col = $this->dm->getChildren($parent);
        $this->assertEquals('Child A', $col->key());

        $this->dm->clear();

        $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent/Child D');
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $this->assertEquals('Child A', $parent->allChildren->key());
    }

    public function testNoChildrenInitOnFlush()
    {
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $this->dm->flush();

        $this->assertFalse($parent->allChildren->isInitialized());
    }

    public function testLazyLoading()
    {
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');

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

    public function testCreateChildren()
    {
        $children = array();
        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child A/Child Create-1';
        $child->name = 'Child A';
        $children[] = $child;

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child A/Child Create-2';
        $child->name = 'Child B';
        $children[] = $child;

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent/Child A');
        $this->assertCount(0, $parent->allChildren);

        $parent->allChildren = $children;
        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent/Child A');
        $this->assertCount(2, $parent->allChildren);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testCreateChildrenNoArray()
    {
        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child A/Child Create-1';
        $child->name = 'Child A';

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent/Child A');
        $this->assertCount(0, $parent->allChildren);

        $parent->allChildren = $child;
        $this->dm->persist($parent);
        $this->dm->flush();
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testCreateChildrenNoObject()
    {
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent/Child A');
        $this->assertCount(0, $parent->allChildren);

        $parent->allChildren = array('This is not an object');
        $this->dm->persist($parent);
        $this->dm->flush();
    }

    public function testSliceChildrenCollection()
    {
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $collection = $parent->allChildren->slice('Child B', 2);
        $this->assertEquals(array('Child B', 'Child C'), array_keys($collection));

        $parent->allChildren->initialize();
        $collection = $parent->allChildren->slice('Child B', 2);
        $this->assertEquals(array('Child B', 'Child C'), array_keys($collection));
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

        $parent->allChildren->remove('Child B');
        $parent->allChildren->remove('Child C');

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child E';
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
        $child->id = '/functional/parent/Child F';
        $child->name = 'Child F';

        $parent->allChildren->add($child);

        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child G';
        $child->name = 'Child G';

        $parent->allChildren->add($child);

        $this->dm->flush();
        $this->dm->clear();
    }

    public function testReplaceChildren()
    {
        /** @var $parent ChildrenTestObj */
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $this->assertCount(4, $parent->allChildren);

        $parent->allChildren->remove('Child A');

        $newChild = new ChildrenTestObj();
        $newChild->name = 'Child A';

        $parent->allChildren->add($newChild);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $this->assertTrue($parent->allChildren->containsKey('Child A'));
        $this->assertFalse($parent->allChildren->containsKey('0'));
        $this->assertEquals('Child A', $parent->allChildren['Child A']->name);
    }

    /**
     * Reorder the children but reset the order in the preUpdate event
     * Tests that the previously compute document change set gets overwritten
     */
    public function testResetResortingChildren()
    {
        $this->listener = new TestResetReorderingListener();
        $this->dm->getEventManager()->addEventListener(array('preUpdate'), $this->listener);

        /** @var $parent ChildrenTestObj */
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');

        $this->assertEquals("Child A", $parent->allChildren->first()->name);

        $parent->allChildren->remove('Child A');

        $newChild = new ChildrenTestObj();
        $newChild->name = 'Child A';

        $parent->allChildren->add($newChild);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');

        $this->assertEquals("Child A", $parent->allChildren->first()->name);

        $this->dm->getEventManager()->removeEventListener(array('preUpdate'), $this->listener);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testMoveByAssignment()
    {
        $other = new ChildrenTestObj();
        $other->id = '/functional/other';
        $this->dm->persist($other);
        $this->dm->flush();
        $this->dm->clear();


        /** @var $parent ChildrenTestObj */
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $this->assertCount(4, $parent->allChildren);

        $other = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/other');
        $other->allChildren->add($parent->allChildren['Child A']);

        $this->dm->flush();
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

        $this->testModifyChildren();

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $this->assertCount(2, $parent->allChildren);

        $data = array(
            'Child G' => $parent->allChildren->last(),
            'Child F' => $parent->allChildren->first(),
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
            'Child I' => $child2,
            'Child H' => $child1,
            'Child F' => $parent->allChildren->last(),
            'Child G' => $parent->allChildren->first(),
            'Child J' => $child3
        );

        $parent->allChildren = new ArrayCollection($data);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
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

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
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

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
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

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $this->assertCount(4, $parent->allChildren);
        $this->assertEquals($expectedOrder, $parent->allChildren->getKeys());
    }

    public function testInsertWithCustomIdStrategy()
    {
        $children = array();
        $child = new ChildrenTestObj();
        $child->name = 'ChildA';
        $children[] = $child;

        $child = new ChildrenTestObj();
        $child->name = 'ChildB';
        $children[] = $child;

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent/Child A');
        $this->assertCount(0, $parent->allChildren);

        $parent->allChildren = $children;
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent/Child A');
        $this->assertCount(2, $parent->allChildren);
    }
}

/**
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObjRepository")
 */
class ChildrenTestObj
{
  /** @PHPCRODM\Id(strategy="repository") */
  public $id;

  /** @PHPCRODM\String(nullable=true) */
  public $name;

  /** @PHPCRODM\Children(filter="*A", fetchDepth=1, cascade="persist") */
  public $aChildren;

  /**
   * @var \Doctrine\ODM\PHPCR\ChildrenCollection
   * @PHPCRODM\Children(fetchDepth=2, cascade="persist")
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
  * @PHPCRODM\Document()
  */
class ChildrenReferrerTestObj
{
  /** @PHPCRODM\Id */
  public $id;

  /** @PHPCRODM\String */
  public $name;

  /** @PHPCRODM\ReferenceOne(targetDocument="ChildrenReferenceableTestObj", cascade="persist") */
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

  /** @PHPCRODM\Children(cascade="persist") */
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
                $childrenCollection->set($name, $children[$name]);
            }

            $document->allChildren = $childrenCollection;
        }
    }
}
