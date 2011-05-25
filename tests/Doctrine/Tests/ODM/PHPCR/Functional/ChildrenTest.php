<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

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
    }

    public function testChildrenCollection()
    {
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ChildrenTestObj', '/functional/parent');
        $col = $this->dm->getChildren($parent);

        $this->assertEquals(count($col), 4);
        $childA = $col['child-a'];
        $this->assertEquals($childA->name, 'Child A');

        $col = $this->dm->getChildren($parent, 'child*');
        $this->assertEquals(count($col), 4);

        $col = $this->dm->getChildren($parent, '*a');
        $this->assertEquals(count($col), 1);
        $this->assertTrue($childA === $col->first());
    }
}

/**
  * @Document(alias="childrenTest")
  */
class ChildrenTestObj
{
  /** @Id */
  public $id;

  /** @String */
  public $name;
}
