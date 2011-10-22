<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @group functional
 */
class ParentTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    private $node;
    private $dm;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testCreate()
    {
        $parent = new ParentTestParentObj();
        $parent->path = '/functional/parent';

        $this->dm->persist($parent);
        $child = new ParentTestChildObj();
        $child->parent = $parent;
        $child->name = 'child';

        $this->dm->persist($child);

        $this->dm->flush();

        $this->assertTrue($this->node->getNode('parent')->hasNode('child'));
        $this->assertEquals('/functional/parent/child', $child->path);
    }

}

/** @PHPCRODM\Document(alias="parent") */
class ParentTestParentObj
{
    /** @PHPCRODM\Id */
    public $path;
}

/** @PHPCRODM\Document(alias="child") */
class ParentTestChildObj
{
    /** @PHPCRODM\Id */
    public $path;

    /** @PHPCRODM\ParentDocument */
    public $parent;

    /** @PHPCRODM\Nodename */
    public $name;
}

