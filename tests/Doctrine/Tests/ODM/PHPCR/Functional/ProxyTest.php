<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;

/**
 * @group functional
 */
class ProxyTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->resetFunctionalNode($this->dm);
    }

    public function testProxyProperty()
    {
        $user = new CmsUser();
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getReference(get_class($user), $user->id);

        $this->assertTrue(isset($user->name), "User is not set on demand");
        $this->assertEquals('Dominik', $user->name, "User is not loaded on demand");
    }

    /**
     * @depends testProxyProperty
     */
    public function testProxyUniqueness()
    {
        $user = new CmsUser();
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getReference(get_class($user), $user->id);
        $this->assertEquals('Dominik', $user->name, "User is not loaded on demand");

        $this->assertSame($this->dm->getReference(get_class($user), $user->id), $user, 'Getting the proxy twice results in a copy');
    }

    public function testProxyImplicit()
    {
        $user = new CmsUser();
        $user->name = 'Dominik';
        $user->username = 'domnikl';
        $user->status = 'developer';
        $assistant = new CmsUser();
        $assistant->username = 'bimbo';
        $user->child = $assistant;

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(null, $user->id);
        $assistant = $this->dm->find(null, $user->id . '/assistant');

        $this->assertSame($assistant, $user->child);
    }

    public function testChildWithoutId()
    {
        $node = $this->resetFunctionalNode($this->dm);
        $parentId = $node->getPath().'/parent';

        $parent = new ParentDoc();
        $parent->id = $parentId;

        $doc = new DocWithoutId();
        $doc->parent = $parent;
        $doc->nodename = 'foo';
        $this->dm->persist($doc);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, $parentId);
        $doc = $parent->children->current();
        $this->assertInstanceOf('Doctrine\Common\Proxy\Proxy', $doc);
        $this->assertInstanceOf('Doctrine\Tests\ODM\PHPCR\Functional\DocWithoutId', $doc);
        $this->assertEquals('foo', $doc->nodename);
        $this->assertInstanceOf('Doctrine\Tests\ODM\PHPCR\Functional\ParentDoc', $doc->parent);
    }


    public function testProxyAwakesOnFields()
    {
        $node = $this->resetFunctionalNode($this->dm);
        $parentId = $node->getPath().'/parent';

        $parent = new ParentDoc();
        $parent->id = $parentId;

        $child = new ChildWithFields();
        $child->parent = $parent;
        $child->nodename = 'foo';
        $child->title = 'child';
        $this->dm->persist($child);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, $parentId);
        $child = $parent->children->current();
        $this->assertFalse($child->__isInitialized__);
        $this->assertEquals('child', $child->title);
    }

    public function testProxyAwakesOnNodeName()
    {
        $node = $this->resetFunctionalNode($this->dm);
        $parentId = $node->getPath().'/parent';

        $parent = new ParentDoc();
        $parent->id = $parentId;

        $child = new ChildWithFields();
        $child->parent = $parent;
        $child->nodename = 'foo';
        $child->title = 'child';
        $this->dm->persist($child);

        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(null, $parentId);
        $child = $parent->children->current();
        $this->assertFalse($child->__isInitialized__);
        $this->assertEquals('foo', $child->nodename);
    }
}

/**
 * @PHPCRODM\Document()
 */
class ParentDoc
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Children(cascade="persist") */
    public $children;
}

/**
 * @PHPCRODM\Document()
 */
class DocWithoutId
{
    /** @PHPCRODM\ParentDocument */
    public $parent;
    /** @PHPCRODM\Nodename */
    public $nodename;
}

/**
 * @PHPCRODM\Document()
 */
class ChildWithFields
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
