<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface,
    Doctrine\ODM\PHPCR\DocumentRepository,
    Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @group functional
 */
class HierarchyTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    private $type;

    private $node;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\NameDoc';
        $this->dm = $this->createDocumentManager();
        $this->node = $this->resetFunctionalNode($this->dm);
        $user = $this->node->addNode('thename');
        $user->setProperty('phpcr:class', $this->type, \PHPCR\PropertyType::STRING);
        $this->dm->getPhpcrSession()->save();
    }

    public function testFind()
    {
        $doc = $this->dm->find($this->type, '/functional/thename');

        $this->assertInstanceOf($this->type, $doc);
        $this->assertEquals('/functional/thename', $doc->id);
        $this->assertEquals('thename', $doc->nodename);

        $this->assertNotNull($doc->parent);
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

        $this->assertNotNull($docNew, "Have to hydrate user object!");
        $this->assertEquals($doc->nodename, $docNew->nodename);
    }

    /**
     * @expectedException Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testNodenameChangeException()
    {
        $doc = $this->dm->find($this->type, '/functional/thename');
        $doc->nodename = 'x';
        $this->dm->flush();
    }

    /**
     * @expectedException Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testParentChangeException()
    {
        $doc = $this->dm->find($this->type, '/functional/thename');
        $doc->parent = new NameDoc();
        $this->dm->flush();
    }

    /**
     * @expectedException Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testIdChangeException()
    {
        $doc = $this->dm->find($this->type, '/functional/thename');
        $doc->id = '/different';
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

    public function testInsertChildWithNewParent()
    {
        $parent = new NameDoc();
        $parent->id = '/functional/parent';

        $child = new NameDoc();
        $child->parent = $parent;
        $child->nodename = 'child';

        $parent->children = array($child);

        $this->dm->persist($child);

        $this->dm->flush();

        $this->assertTrue($this->node->getNode('parent')->hasNode('child'));
        $this->assertEquals('/functional/parent/child', $child->id);
    }

    // TODO: move? is to be done through phpcr session directly

}

/**
 * @PHPCRODM\Document(alias="name")
 */
class NameDoc
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\Nodename */
    public $nodename;
    /** @PHPCRODM\ParentDocument */
    public $parent;
    /** @PHPCRODM\Children */
    public $children;
}
