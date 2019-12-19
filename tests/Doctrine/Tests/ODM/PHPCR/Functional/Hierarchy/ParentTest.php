<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Hierarchy;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\ODM\PHPCR\Document\Generic;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Id\IdException;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\PHPCRException;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;

/**
 * Test for the Parent mapping.
 *
 * @group functional
 */
class ParentTest extends PHPCRFunctionalTestCase
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
    private $type = NameDoc::class;

    /**
     * @var NodeInterface
     */
    private $node;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);

        $root = $this->dm->getPhpcrSession()->getNode('/');
        if ($root->hasNode('childOfRoot')) {
            $root->getNode('childOfRoot')->remove();
        }

        $user = $this->node->addNode('thename');
        $user->setProperty('phpcr:class', $this->type, PropertyType::STRING);

        $this->dm->getPhpcrSession()->save();
    }

    public function testChildMapsParent()
    {
        $doc = $this->dm->find($this->type, '/functional/thename');

        $this->assertInstanceOf($this->type, $doc);
        $this->assertEquals('/functional/thename', $doc->id);
        $this->assertEquals('thename', $doc->nodename);

        $this->assertTrue($doc->parent instanceof Proxy);
        $this->assertEquals('/functional', $doc->parent->getId());

        return $doc;
    }

    public function testInsert()
    {
        $doc = new NameDoc();
        $doc->id = '/functional/test';

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->assertEquals('test', $doc->nodename);
        $this->assertNotNull($doc->parent);
        $this->assertEquals('functional', $doc->parent->getNodename());
        $this->dm->clear();

        $docNew = $this->dm->find($this->type, '/functional/test');

        $this->assertNotNull($docNew, 'Have to hydrate user object!');
        $this->assertEquals($doc->nodename, $docNew->nodename);
    }

    public function testParentChangeException()
    {
        $doc = $this->dm->find($this->type, '/functional/thename');
        $doc->parent = new NameDoc();

        $this->expectException(PHPCRException::class);
        $this->dm->flush();
    }

    public function testIdChangeException()
    {
        $doc = $this->dm->find($this->type, '/functional/thename');
        $doc->id = '/different';

        $this->expectException(PHPCRException::class);
        $this->dm->flush();
    }

    public function testInsertChildWithManualPath()
    {
        $parent = new NameDoc();
        $parent->id = '/functional/parent';

        $this->dm->persist($parent);
        $this->dm->flush();

        $child = new NameDoc();
        $child->id = '/functional/parent/child';

        $this->dm->persist($child);
        $this->dm->flush();
    }

    public function testInsertWithParentIdStrategy()
    {
        $doc = $this->dm->find($this->type, '/functional/thename');
        $child = new NameDoc();
        $child->parent = $doc;
        $child->nodename = 'child';

        $this->dm->persist($child);

        $this->dm->flush();

        $this->assertTrue($this->node->getNode('thename')->hasNode('child'));
        $this->assertEquals('/functional/thename/child', $child->id);
    }

    public function testInsertGrandchildWithParentIdStrategy()
    {
        $doc = $this->dm->find($this->type, '/functional/thename');
        $child = new NameDoc();
        $child->parent = $doc;
        $child->nodename = 'child';

        $this->dm->persist($child);

        $this->dm->flush();

        $doc = $this->dm->find($this->type, '/functional/thename/child');

        $grandchild = new NameDoc();
        $grandchild->parent = $doc;
        $grandchild->nodename = 'grandchild';

        $doc->children = [$grandchild];

        $this->dm->persist($grandchild);

        $this->dm->flush();

        $this->assertTrue($this->node->getNode('thename')->getNode('child')->hasNode('grandchild'));
        $this->assertEquals('/functional/thename/child/grandchild', $grandchild->id);
    }

    public function testInsertChildWithNewParent()
    {
        $parent = new NameDoc();
        $parent->id = '/functional/parent';

        $child = new NameDoc();
        $child->parent = $parent;
        $child->nodename = 'child';

        $parent->children = [$child];

        $this->dm->persist($child);

        $this->dm->flush();

        $this->assertTrue($this->node->getNode('parent')->hasNode('child'));
        $this->assertEquals('/functional/parent/child', $child->id);
    }

    public function testInsertGrandchildWithNewParent()
    {
        $parent = new NameDoc();
        $parent->id = '/functional/parent';

        $child = new NameDoc();
        $child->parent = $parent;
        $child->nodename = 'child';

        $parent->children = [$child];

        // the grand child document
        $grandchild = new NameDoc();
        $grandchild->parent = $child;
        $grandchild->nodename = 'grandchild';

        $child->children = [$grandchild];

        $this->dm->persist($child);

        $this->dm->flush();

        $this->assertTrue($this->node->getNode('parent')->hasNode('child'));
        $this->assertEquals('/functional/parent/child', $child->id);

        $this->assertTrue($this->node->getNode('parent')->getNode('child')->hasNode('grandchild'));
        $this->assertEquals('/functional/parent/child/grandchild', $grandchild->id);
    }

    public function testChildOfRoot()
    {
        $root = $this->dm->find(null, '/');
        $child = new NameDoc();
        $child->parent = $root;
        $child->nodename = 'childOfRoot';
        $this->dm->persist($child);
        $this->dm->flush();
        $this->assertEquals('/childOfRoot', $child->id);
    }

    public function testParentOfReference()
    {
        $doc = $this->dm->find($this->type, '/functional/thename');
        $doc->node->addMixin('mix:referenceable');
        $this->dm->getPhpcrSession()->save();

        $referrer = new NameDocWithRef();
        $referrer->id = '/functional/referrer';
        $referrer->ref = $doc;
        $this->dm->persist($referrer);

        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->find(null, '/functional/referrer');
        $this->assertInstanceOf(Generic::class, $referrer->ref->parent);
    }

    /**
     * Create a node with a bad name and allow it to be discovered
     * among its parent node's children without explicity persisting it.
     */
    public function testIllegalNameNewChild()
    {
        $parent = $this->dm->find($this->type, '/functional/thename');

        $child = new NameDoc();
        $child->nodename = 'bad/name';
        $child->parent = $parent;
        $parent->children->add($child);

        $this->expectException(IdException::class);
        $this->dm->flush();
    }

    /**
     * Create a node with a bad name and allow it to be discovered
     * among its parent node's children while also explicitly
     * persisting it.
     */
    public function testIllegalNameManagedChild()
    {
        $parent = $this->dm->find($this->type, '/functional/thename');

        $child = new NameDoc();
        $child->nodename = 'bad/name';
        $child->parent = $parent;
        $parent->children->add($child);

        $this->expectException(IdException::class);
        $this->dm->persist($child);
    }
}

/**
 * @PHPCRODM\Document()
 */
class NameDoc
{
    /** @PHPCRODM\ParentDocument */
    public $parent;

    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Node */
    public $node;

    /** @PHPCRODM\Nodename */
    public $nodename;

    /** @PHPCRODM\Children(cascade="persist") */
    public $children;

    /** @PHPCRODM\Child(cascade="persist") */
    public $child;
}

/**
 * @PHPCRODM\Document()
 */
class NameDocWithRef extends NameDoc
{
    /** @PHPCRODM\ReferenceOne(cascade="persist") */
    public $ref;
}
