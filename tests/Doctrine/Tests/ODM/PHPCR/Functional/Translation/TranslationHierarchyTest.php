<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Translation;

use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface,
    Doctrine\ODM\PHPCR\DocumentRepository,
    Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM,
    Doctrine\ODM\PHPCR\Proxy\Proxy;

use Doctrine\Tests\Models\Translation\Article;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy;

/**
 * @group functional
 */
class TranslationHierarchyTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    private $type;

    private $node;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\Translation\NameDoc';
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

/*
 * TODO: these tests can be reactived to test getting detached documents in a non-default
 * language, once this feature is implemented.
 *
    public function testInsertChild()
    {
        $parent = $this->dm->find($this->type, '/functional/thename');

        $child = new Article();
        $child->parent = $parent;
        $child->id = '/functional/thename/child';
        $child->author = 'John Doe';
        $child->topic = 'Some interesting subject';
        $child->text = 'Lorem ipsum...';

        $this->dm->persist($child);
        $this->dm->bindTranslation($child, 'fr');

        $this->dm->flush();

        $this->assertTrue($this->node->getNode('thename')->hasNode('child'));
        $this->assertEquals('/functional/thename/child', $child->id);

        $this->assertEquals('fr', $child->locale);
        $this->assertEquals('fr', $parent->locale);
    }

    function testFindPropagateLocale()
    {
        $child = new Article();
        $child->id = '/functional/thename/child';
        $child->author = 'John Doe';
        $child->text = 'Lorem ipsum...';
        $child->topic = 'Interesting Topic';
        $this->dm->persist($child);
        $this->dm->bindTranslation($child, 'en');
        $child->topic = 'Sujet interessant';
        $this->dm->bindTranslation($child, 'fr');

        $this->dm->clear();

        $doc = $this->dm->findTranslation($this->type, '/functional/thename', 'fr');

        $this->assertTrue($doc->child instanceof Proxy);
        $this->assertEquals('fr', $doc->locale);
        $this->assertEquals('fr', $doc->child->locale);
        $this->assertEquals('Sujet interessant', $doc->child->topic);
    }

    function testTranslatePropagateLocale()
    {
        $child = new Article();
        $child->id = '/functional/thename/child';
        $child->author = 'John Doe';
        $child->text = 'Lorem ipsum...';
        $child->topic = 'Interesting Topic';
        $this->dm->persist($child);
        $this->dm->bindTranslation($child, 'en');
        $child->topic = 'Sujet interessant';
        $this->dm->bindTranslation($child, 'fr');

        $this->dm->clear();

        $doc = $this->dm->findTranslation($this->type, '/functional/thename', 'fr');

        $this->dm->translate($doc, 'en');
        $this->assertTrue($doc->child instanceof Proxy);
        $this->assertEquals('en', $doc->locale);
        $this->assertEquals('en', $doc->child->locale);
        $this->assertEquals('Interesting Topic', $doc->child->topic);
    }

*/
}

/**
 * @PHPCRODM\Document(translator="attribute")
 */
class NameDoc
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Locale */
    public $locale;

    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\Nodename */
    public $nodename;
    /** @PHPCRODM\ParentDocument */
    public $parent;
    /** @PHPCRODM\Children */
    public $children;
    /** @PHPCRODM\Child */
    public $child;
    /** @PHPCRODM\String */
    public $title;
}
