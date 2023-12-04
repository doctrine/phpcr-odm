<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Hierarchy;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\ChildrenCollection;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
use Doctrine\ODM\PHPCR\PersistentCollection;
use Doctrine\ODM\PHPCR\PHPCRException;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\RepositoryInterface;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Test for the Children mapping.
 *
 * @group functional
 */
class ChildrenTest extends PHPCRFunctionalTestCase
{
    private DocumentManager $dm;

    private string $type = ChildrenTestObj::class;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager();
        $this->resetFunctionalNode($this->dm);

        $parent = new ChildrenTestObj();
        $parent->id = '/functional/parent';
        $parent->name = 'Parent';
        $this->dm->persist($parent);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function createChildren(): void
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

    public function testChildrenCollection(): void
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
        $this->assertSame($childA, $col->first());

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

    public function testSliceChildrenCollection(): void
    {
        $this->createChildren();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $collection = $parent->allChildren->slice('Child B', 2);
        $this->assertEquals(['Child B', 'Child C'], array_keys($collection));

        $parent->allChildren->initialize();
        $collection = $parent->allChildren->slice('Child B', 2);
        $this->assertEquals(['Child B', 'Child C'], array_keys($collection));
    }

    public function testNoChildrenInitOnFlush(): void
    {
        $this->createChildren();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->dm->flush();

        $this->assertFalse($parent->allChildren->isInitialized());
    }

    public function testLazyLoading(): void
    {
        $this->createChildren();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertInstanceOf($this->type, $parent);

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
        $this->assertInstanceOf(PersistentCollection::class, $parent->aChildren);
        $this->assertTrue($parent->aChildren->isInitialized());

        $parent->allChildren->remove('Child C');
        $this->assertCount(3, $parent->allChildren);
        $this->assertTrue($parent->allChildren->isInitialized());
    }

    public function testChildrenOfReference(): void
    {
        $referrerTestObj = new ChildrenReferrerTestObj();
        $referrerTestObj->id = '/functional/referrerTestObj';
        $referrerTestObj->name = 'referrerTestObj';

        $refererenceableTestObj = new ChildrenReferenceableTestObj();
        $refererenceableTestObj->id = '/functional/referenceableTestObj';
        $refererenceableTestObj->name = 'referenceableTestObj';
        $referrerTestObj->reference = $refererenceableTestObj;

        $this->dm->persist($referrerTestObj);

        $ChildrenTestObj = new ChildrenTestObj();
        $ChildrenTestObj->id = '/functional/referenceableTestObj/childrenTestObj';
        $ChildrenTestObj->name = 'childrenTestObj';

        $this->dm->persist($ChildrenTestObj);
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find(null, '/functional/referrerTestObj');

        $this->assertCount(1, $referrer->reference->allChildren);
        $this->assertEquals('childrenTestObj', $referrer->reference->allChildren->first()->name);
    }

    /**
     * New parent, insert an array of children with an assigned id.
     */
    public function testInsertNewAssignedId(): void
    {
        $parent = $this->dm->find($this->type, '/functional/parent');
        $new = new ChildrenTestObj();
        $new->id = '/functional/parent/new';

        $children = [];
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
    public function testInsertExistingAssignedId(): void
    {
        $parent = $this->dm->find($this->type, '/functional/parent');

        $children = [];
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
    public function testInsertExistingNamedChild(): void
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
     * New parent, insert children with parent and name strategy.
     */
    public function testInsertNewNamedChild(): void
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
    public function testInsertExistingCustomIdStrategy(): void
    {
        $parent = $this->dm->find($this->type, '/functional/parent');

        $children = [];
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
    public function testInsertExistingAutoname(): void
    {
        $parent = $this->dm->find(null, '/functional/parent');
        $this->assertInstanceOf(ChildrenTestObj::class, $parent);
        $parent->allChildren->add(new ChildrenAutonameTestObj());

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/parent');
        $this->assertCount(1, $parent->allChildren);
    }

    /**
     * Insert the parent and children at the same time, autoname strategy.
     */
    public function testInsertNewAutoname(): void
    {
        $new = new ChildrenTestObj();
        $new->id = '/functional/parent/new';
        $new->allChildren->add(new ChildrenAutonameTestObj());

        $this->dm->persist($new);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/parent/new');
        $this->assertInstanceOf(ChildrenTestObj::class, $parent);
        $this->assertCount(1, $parent->allChildren);
    }

    /**
     * A children field must always be a collection/array. It can't be a single document.
     */
    public function testInsertNoArray(): void
    {
        $child = new ChildrenTestObj();
        $child->id = '/functional/parent/Child Create-1';
        $child->name = 'Child A';

        $parent = $this->dm->find($this->type, '/functional/parent');

        $parent->allChildren = $child;
        $this->dm->persist($parent);

        $this->expectException(PHPCRException::class);
        $this->dm->flush();
    }

    public function testInsertNoObject(): void
    {
        $parent = $this->dm->find($this->type, '/functional/parent');

        $parent->allChildren = ['This is not an object'];
        $this->dm->persist($parent);

        $this->expectException(PHPCRException::class);
        $this->dm->flush();
    }

    public function testRemoveChildParent(): void
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

    public function testModifyChildren(): void
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

    public function testReplaceChildren(): void
    {
        $this->createChildren();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertInstanceOf($this->type, $parent);
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
    public function testReorderChildren(): void
    {
        if (!$this->dm
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

        $data = [
            'Child G' => $parent->allChildren->last(),
            'Child F' => $parent->allChildren->first(),
        ];

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

        $data = [
            'Child I' => $child2,
            'Child H' => $child1,
            'Child F' => $parent->allChildren->last(),
            'Child G' => $parent->allChildren->first(),
            'Child J' => $child3,
        ];

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

        $keys = [
            'Child I',
            'Child H',
            'Child F',
            'Child J',
            'Child G',
        ];

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertCount(count($keys), $parent->allChildren);
        $this->assertEquals($keys, $parent->allChildren->getKeys());
    }

    public function testReorderChildrenLast(): void
    {
        if (!$this->dm
            ->getPhpcrSession()
            ->getRepository()
            ->getDescriptor(RepositoryInterface::NODE_TYPE_MANAGEMENT_ORDERABLE_CHILD_NODES_SUPPORTED)
        ) {
            $this->markTestSkipped('Reordering of children not supported');
        }

        $this->createChildren();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertInstanceOf($this->type, $parent);
        $this->assertCount(4, $parent->allChildren);

        $childrenCollection = $parent->allChildren;
        $children = $childrenCollection->toArray();

        $childrenCollection->clear();

        $expectedOrder = ['Child A', 'Child D', 'Child C', 'Child B'];

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
     * Tests that the previously compute document change set gets overwritten.
     *
     * @depends testReorderChildren
     */
    public function testResetReorderChildren(): void
    {
        $this->createChildren();

        $listener = new TestResetReorderingListener();
        $this->dm->getEventManager()->addEventListener(['preUpdate'], $listener);

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertInstanceOf($this->type, $parent);

        $this->assertEquals('Child A', $parent->allChildren->first()->name);

        $parent->allChildren->remove('Child A');

        $newChild = new ChildrenTestObj();
        $newChild->name = 'Child A';

        $parent->allChildren->add($newChild);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');

        $this->assertEquals('Child A', $parent->allChildren->first()->name);

        $this->dm->getEventManager()->removeEventListener(['preUpdate'], $listener);
    }

    /**
     * Rename the nodename of a child.
     */
    public function testRenameChildren(): void
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
     * Move a child out of the children collection.
     */
    public function testMoveChildren(): void
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

    public function testMoveByAssignment(): void
    {
        $this->createChildren();

        $other = new ChildrenTestObj();
        $other->id = '/functional/other';
        $this->dm->persist($other);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find($this->type, '/functional/parent');
        $this->assertInstanceOf($this->type, $parent);
        $this->assertCount(4, $parent->allChildren);

        $other = $this->dm->find($this->type, '/functional/other');
        $other->allChildren->add($parent->allChildren['Child A']);

        $this->expectException(PHPCRException::class);
        $this->dm->flush();
    }

    public function testMoveByUpdateId(): void
    {
        $this->createChildren();
        $parent = $this->dm->find($this->type, '/functional/parent');
        $child = $parent->allChildren->first();

        $child->id = '/functional/elsewhere';

        $this->expectException(PHPCRException::class);
        $this->dm->flush();
    }
}

#[PHPCR\Document(repositoryClass: ChildrenTestObjRepository::class)]
class ChildrenTestObj
{
    public function __construct()
    {
        $this->aChildren = new ArrayCollection();
        $this->allChildren = new ArrayCollection();
    }

    #[PHPCR\Id(strategy: 'repository')]
    public $id;

    #[PHPCR\Field(type: 'string', nullable: true)]
    public $name;

    #[PHPCR\Children(filter: '*A', fetchDepth: 1, cascade: 'persist')]
    public $aChildren;

    /**
     * @var ChildrenCollection
     */
    #[PHPCR\Children(fetchDepth: 2, cascade: 'persist')]
    public $allChildren;
}

class ChildrenTestObjRepository extends DocumentRepository implements RepositoryIdInterface
{
    public function generateId(object $document, object $parent = null): string
    {
        if ($document->id) {
            return $document->id;
        }

        $parent = $parent ? $parent->id : '/functional';

        return $parent.'/'.$document->name;
    }
}

#[PHPCR\Document]
class ChildrenAutonameTestObj
{
    #[PHPCR\Id(strategy: 'auto')]
    public $id;

    #[PHPCR\ParentDocument]
    public $parent;
}

#[PHPCR\Document]
class ChildrenParentAndNameTestObj
{
    #[PHPCR\ParentDocument]
    public $parent;

    #[PHPCR\Id(strategy: 'parent')]
    public $id;

    #[PHPCR\Nodename]
    public $name;
}

#[PHPCR\Document]
class ChildrenReferrerTestObj
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Field(type: 'string')]
    public $name;

    #[PHPCR\ReferenceOne(targetDocument: ChildrenReferenceableTestObj::class, cascade: 'persist')]
    public $reference;
}

#[PHPCR\Document(referenceable: true)]
class ChildrenReferenceableTestObj
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Field(type: 'string')]
    public $name;

    #[PHPCR\Children(cascade: 'persist')]
    public $allChildren;
}

class TestResetReorderingListener
{
    public function preUpdate(LifecycleEventArgs $e): void
    {
        $document = $e->getObject();
        if ($document instanceof ChildrenTestObj && 'Child B' === $document->allChildren->first()->name) {
            $childrenCollection = $document->allChildren;
            $children = $childrenCollection->toArray();

            $childrenCollection->clear();

            $expectedOrder = ['Child A', 'Child B', 'Child C', 'Child D'];

            foreach ($expectedOrder as $name) {
                if (!array_key_exists($name, $children)) {
                    throw new AssertionFailedError("Missing index '$name' in ".implode(', ', array_keys($children)));
                }
                $childrenCollection->set($name, $children[$name]);
            }

            $document->allChildren = $childrenCollection;
        }
    }
}
