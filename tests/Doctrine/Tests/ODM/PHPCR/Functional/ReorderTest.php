<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Document\Generic;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;

/**
 * @group functional
 */
class ReorderTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var array
     */
    private $childrenNames;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager([__DIR__]);
        $repository = $this->dm->getPhpcrSession()->getRepository();
        if (!$repository->getDescriptor('node.type.management.orderable.child.nodes.supported')) {
            $this->markTestSkipped('PHPCR repository doesn\'t support orderable child nodes');
        }
        $node = $this->resetFunctionalNode($this->dm);
        $parent = $this->dm->find(null, $node->getPath());

        $node1 = new Generic();
        $node1->setParentDocument($parent);
        $node1->setNodename('source');
        $this->dm->persist($node1);

        $this->childrenNames = ['first', 'second', 'third', 'fourth'];
        foreach ($this->childrenNames as $childName) {
            $child = new Generic();
            $child->setNodename($childName);
            $child->setParentDocument($node1);
            $this->dm->persist($child);
        }

        $node2 = new Generic();
        $node2->setNodename('target');
        $node2->setParentDocument($parent);
        $this->dm->persist($node2);

        $this->dm->flush();
        $this->dm->clear();
    }

    public function testReorder(): void
    {
        $parent = $this->dm->find(null, '/functional/source');

        $children = $parent->getChildren();

        $this->assertSame($this->childrenNames, $this->getChildrenNames($children));

        $this->dm->reorder($parent, 'first', 'second', false);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/source');
        $this->assertSame(['second', 'first', 'third', 'fourth'], $this->getChildrenNames($parent->getChildren()));
    }

    public function testReorderMultiple(): void
    {
        $parent = $this->dm->find(null, '/functional/source');

        $children = $parent->getChildren();
        $this->assertSame($this->childrenNames, $this->getChildrenNames($children));
        $this->dm->reorder($parent, 'first', 'second', false);
        $this->dm->reorder($parent, 'third', 'fourth', false);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/source');
        $this->assertSame(['second', 'first', 'fourth', 'third'], $this->getChildrenNames($parent->getChildren()));
    }

    public function testReorderNoop(): void
    {
        $parent = $this->dm->find(null, '/functional/source');
        $children = $parent->getChildren();
        $this->assertSame($this->childrenNames, $this->getChildrenNames($children));

        $this->dm->reorder($parent, 'first', 'second', true);
        $this->dm->flush();
        $this->assertSame($this->childrenNames, $this->getChildrenNames($children));
    }

    public function testReorderNoObject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->reorder('parent', 'first', 'second', false);
    }

    public function testReorderBeforeFirst(): void
    {
        $parent = $this->dm->find(null, '/functional/source');
        $children = $parent->getChildren();
        $this->assertSame($this->childrenNames, $this->getChildrenNames($children));

        $this->dm->reorder($parent, 'second', 'first', true);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/source');
        $this->assertSame(['second', 'first', 'third', 'fourth'], $this->getChildrenNames($parent->getChildren()));
    }

    public function testReorderAfterLast(): void
    {
        $parent = $this->dm->find(null, '/functional/source');
        $children = $parent->getChildren();
        $this->assertSame($this->childrenNames, $this->getChildrenNames($children));

        $this->dm->reorder($parent, 'first', 'fourth', false);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/source');
        $this->assertSame(['second', 'third', 'fourth', 'first'], $this->getChildrenNames($parent->getChildren()));
    }

    public function testReorderUpdatesChildren(): void
    {
        $parent = $this->dm->find(null, '/functional/source');
        $children = $parent->getChildren();
        $this->assertSame($this->childrenNames, $this->getChildrenNames($children));

        $this->dm->reorder($parent, 'first', 'second', false);
        $this->dm->flush();

        $this->dm->clear();
        $parent = $this->dm->find(null, '/functional/source');
        $this->assertSame(['second', 'first', 'third', 'fourth'], $this->getChildrenNames($parent->getChildren()));
    }

    public function testReorderBeforeMove(): void
    {
        $parent = $this->dm->find(null, '/functional/source');
        $this->dm->reorder($parent, 'first', 'second', false);
        $this->dm->move($parent, '/functional/target/new');
        $this->dm->flush();

        $parent = $this->dm->find(null, '/functional/target/new');
        $this->assertSame(['second', 'first', 'third', 'fourth'], $this->getChildrenNames($parent->getChildren()));
    }

    public function testReorderAfterMove(): void
    {
        $parent = $this->dm->find(null, '/functional/source');
        $this->dm->move($parent, '/functional/target/new');
        $this->dm->reorder($parent, 'first', 'second', false);
        $this->dm->flush();

        $parent = $this->dm->find(null, '/functional/target/new');
        $this->assertSame(['second', 'first', 'third', 'fourth'], $this->getChildrenNames($parent->getChildren()));
    }

    public function testRemoveAfterReorder(): void
    {
        $parent = $this->dm->find(null, '/functional/source');
        $this->dm->reorder($parent, 'first', 'second', false);
        $this->dm->remove($parent);
        $this->dm->flush();

        $parent = $this->dm->find(null, '/functional/source');
        $this->assertNull($parent);
    }

    public function testReorderAfterRemove(): void
    {
        $parent = $this->dm->find(null, '/functional/source');
        $this->dm->remove($parent);
        $this->expectException(InvalidArgumentException::class);
        $this->dm->reorder($parent, 'first', 'second', false);
    }

    public function testReorderParentProxy(): void
    {
        $first = $this->dm->find(null, '/functional/source/first');
        $parent = $first->getParentDocument();
        $this->dm->reorder($parent, 'first', 'second', false);
        $this->dm->flush();
        $this->assertSame(['second', 'first', 'third', 'fourth'], $this->getChildrenNames($parent->getChildren()));
    }

    public function testNumericNodes()
    {
        $parent = $this->dm->find(null, '/functional/source');

        // The ChildrenCollection calls getKeys when taking a snapshot, and that can convert numeric string
        // node names into integer node names
        $numericNodes = ['2017', '2018'];
        foreach ($numericNodes as $numericNode) {
            $child = new Generic();
            $child->setNodename($numericNode);
            $child->setParentDocument($parent);
            $this->dm->persist($child);
        }

        $this->dm->flush();
        $this->dm->clear();

        // Force the numeric children to load and take a snapshot.
        $parent = $this->dm->find(null, '/functional/source');
        $parent->getChildren()->initialize();
        $parent->getChildren()->takeSnapshot();

        // Assert that no changesets are created, as nothing has changed.
        $this->dm->getUnitOfWork()->computeChangeSets();
        $this->assertEmpty($this->dm->getUnitOfWork()->getScheduledUpdates());
    }

    private function getChildrenNames($children): array
    {
        $result = [];
        foreach ($children as $name => $child) {
            $result[] = $name;
        }

        return $result;
    }
}
