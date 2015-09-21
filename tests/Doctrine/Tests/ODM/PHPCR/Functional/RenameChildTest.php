<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;
use Doctrine\Tests\Models\Translation\Article;
use PHPCR\PropertyType;
use Doctrine\Tests\Models\CMS\CmsTeamUser;

/**
 * @group functional
 */
class RenameChildTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    private $type;

    private $node;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);

        $parent = new RCTParent();
        $parent->id = '/functional/parent';
        $parent->title = 'Test';

        $child = new RCTChild();
        $child->parent = $parent;
        $child->nodename = 'test';
        $child->title = 'Testchild';

        $this->dm->persist($parent);
        $this->dm->persist($child);

        $this->dm->flush();
    }

    public function testRenameWithOneChild()
    {
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/parent');
        foreach ($parent->children as $name => $child) {
            // just make sure the children collection is initialized
        }

        $child = $this->dm->find(null, '/functional/parent/test');
        $this->assertEquals('test', $child->nodename);

        $child->nodename = 'renamed';
        $this->dm->flush();

        $renamed = $this->dm->find(null, '/functional/parent/renamed');

        $this->assertNotNull($renamed);
        $this->assertEquals('Testchild', $renamed->title);
    }

    public function testRenameWithParentChange()
    {
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/parent');
        foreach ($parent->children as $name => $child) {
            // just make sure the children collection is initialized
        }

        $child = $this->dm->find(null, '/functional/parent/test');
        $this->assertEquals('test', $child->nodename);

        $child->nodename = 'renamed';
        $parent->title = 'Changed Test';

        $this->dm->flush();

        $renamed = $this->dm->find(null, '/functional/parent/renamed');

        $this->assertNotNull($renamed);
        $this->assertEquals('Testchild', $renamed->title);
        $this->assertEquals('Changed Test', $renamed->parent->title);
    }

    public function testRenameWithTwoChildren()
    {
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/parent');
        $secondChild = new RCTChild();

        $secondChild->parent = $parent;
        $secondChild->nodename = 'test2';

        $secondChild->title = 'Testchild 2';

        $this->dm->persist($secondChild);
        $this->dm->flush();

        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/parent');
        foreach ($parent->children as $name => $child) {
            // just make sure the children collection is initialized
        }

        $child = $this->dm->find(null, '/functional/parent/test');
        $this->assertEquals('test', $child->nodename);

        $child->nodename = 'renamed';
        $this->dm->flush();

        $renamed = $this->dm->find(null, '/functional/parent/renamed');

        $this->assertNotNull($renamed);
        $this->assertEquals('Testchild', $renamed->title);
    }
}

/**
 * @PHPCRODM\Document()
 */
class RCTParent
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Field(type="string") */
    public $title;

    /** @PHPCRODM\Children */
    public $children;

    /** @PHPCRODM\PreUpdate */
    public function preUpdate()
    {
        // NOOP
    }
}

/**
 * @PHPCRODM\Document()
 */
class RCTChild
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\ParentDocument */
    public $parent;

    /** @PHPCRODM\Nodename */
    public $nodename;

    /** @PHPCRODM\Field(type="string") */
    public $title;
}
