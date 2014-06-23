<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Document\Generic;
use PHPCR\NodeInterface;

/**
 * @group functional
 */
class ReorderTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var array
     */
    private $childrenNames;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $repository = $this->dm->getPhpcrSession()->getRepository();
        if (!$repository->getDescriptor('node.type.management.orderable.child.nodes.supported')) {
            $this->markTestSkipped('PHPCR repository doesn\'t support orderable child nodes');
        }
        $this->node = $this->resetFunctionalNode($this->dm);
        $parent = $this->dm->find(null, $this->node->getPath());

        $node1 = new Generic();
        $node1->setParentDocument($parent);
        $node1->setNodename('source');
        $this->dm->persist($node1);

        $this->childrenNames = array('first', 'second', 'third', 'fourth');
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

    public function testReorder()
    {
        $parent = $this->dm->find(null, '/functional/source');

        $children = $parent->getChildren();

        $this->assertSame($this->childrenNames, $this->getChildrenNames($children));

        $this->dm->reorder($parent, 'first', 'second', false);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/source');
        $this->assertSame(array('second', 'first', 'third', 'fourth'), $this->getChildrenNames($parent->getChildren()));
    }

    public function testReorderMultiple()
    {
        $parent = $this->dm->find(null, '/functional/source');

        $children = $parent->getChildren();
        $this->assertSame($this->childrenNames, $this->getChildrenNames($children));
        $this->dm->reorder($parent, 'first', 'second', false);
        $this->dm->reorder($parent, 'third', 'fourth', false);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/source');
        $this->assertSame(array('second', 'first', 'fourth', 'third'), $this->getChildrenNames($parent->getChildren()));
    }

    public function testReorderNoop()
    {
        $parent = $this->dm->find(null, '/functional/source');
        $children = $parent->getChildren();
        $this->assertSame($this->childrenNames, $this->getChildrenNames($children));

        $this->dm->reorder($parent, 'first', 'second', true);
        $this->dm->flush();
        $this->assertSame($this->childrenNames, $this->getChildrenNames($children));
    }

    public function testReorderNoObject()
    {
        $this->setExpectedException('\Doctrine\ODM\PHPCR\Exception\InvalidArgumentException');
        $this->dm->reorder('parent', 'first', 'second', false);
        $this->dm->flush();
    }

    public function testReorderBeforeFirst()
    {
        $parent = $this->dm->find(null, '/functional/source');
        $children = $parent->getChildren();
        $this->assertSame($this->childrenNames, $this->getChildrenNames($children));

        $this->dm->reorder($parent, 'second', 'first', true);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/source');
        $this->assertSame(array('second', 'first', 'third', 'fourth'), $this->getChildrenNames($parent->getChildren()));
    }

    public function testReorderAfterLast()
    {
        $parent = $this->dm->find(null, '/functional/source');
        $children = $parent->getChildren();
        $this->assertSame($this->childrenNames, $this->getChildrenNames($children));

        $this->dm->reorder($parent, 'first', 'fourth', false);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/source');
        $this->assertSame(array('second', 'third', 'fourth', 'first'), $this->getChildrenNames($parent->getChildren()));
    }

    public function testReorderUpdatesChildren()
    {
        $parent = $this->dm->find(null, '/functional/source');
        $children = $parent->getChildren();
        $this->assertSame($this->childrenNames, $this->getChildrenNames($children));

        $this->dm->reorder($parent, 'first', 'second', false);
        $this->dm->flush();

        $this->dm->clear();
        $parent = $this->dm->find(null, '/functional/source');
        $this->assertSame(array('second', 'first', 'third', 'fourth'), $this->getChildrenNames($parent->getChildren()));
    }

    public function testReorderBeforeMove()
    {
        $parent = $this->dm->find(null, '/functional/source');
        $this->dm->reorder($parent, 'first', 'second', false);
        $this->dm->move($parent, '/functional/target/new');
        $this->dm->flush();

        $parent = $this->dm->find(null, '/functional/target/new');
        $this->assertSame(array('second', 'first', 'third', 'fourth'), $this->getChildrenNames($parent->getChildren()));
    }

    public function testReorderAfterMove()
    {
        $parent = $this->dm->find(null, '/functional/source');
        $this->dm->move($parent, '/functional/target/new');
        $this->dm->reorder($parent, 'first', 'second', false);
        $this->dm->flush();

        $parent = $this->dm->find(null, '/functional/target/new');
        $this->assertSame(array('second', 'first', 'third', 'fourth'), $this->getChildrenNames($parent->getChildren()));
    }

    public function testRemoveAfterReorder()
    {
        $parent = $this->dm->find(null, '/functional/source');
        $this->dm->reorder($parent, 'first', 'second', false);
        $this->dm->remove($parent);
        $this->dm->flush();

        $parent = $this->dm->find(null, '/functional/source');
        $this->assertNull($parent);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testReorderAfterRemove()
    {
        $parent = $this->dm->find(null, '/functional/source');
        $this->dm->remove($parent);
        $this->dm->reorder($parent, 'first', 'second', false);
    }

    public function testReorderParentProxy()
    {
        $first = $this->dm->find(null, '/functional/source/first');
        $parent = $first->getParentDocument();
        $this->dm->reorder($parent, 'first', 'second', false);
        $this->dm->flush();
        $this->assertSame(array('second', 'first', 'third', 'fourth'), $this->getChildrenNames($parent->getChildren()));
    }

    private function getChildrenNames($children)
    {
        $result = array();
        foreach ($children as $name => $child) {
            $result[] = $name;
        }
        return $result;
    }
}
