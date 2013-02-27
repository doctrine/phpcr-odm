<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Translation;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy;

use Doctrine\Tests\Models\Translation\Article;

/**
 * @group functional
 */
class TranslationHierarchyTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * Class name of the document class
     * @var string
     */
    private $type;

    /**
     * @var \PHPCR\NodeInterface
     */
    private $node;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\Models\Translation\Article';
        $this->dm = $this->createDocumentManager();
        $this->dm->setLocaleChooserStrategy(new LocaleChooser(array('en' => array('fr'), 'fr' => array('en')), 'en'));
        $this->node = $this->resetFunctionalNode($this->dm);
        $user = $this->node->addNode('thename');
        $user->setProperty('phpcr:class', $this->type, \PHPCR\PropertyType::STRING);
        $user->setProperty('phpcr_locale:fr-topic', 'french', \PHPCR\PropertyType::STRING);
        $user->setProperty('phpcr_locale:frnullfields', array('text', 'settings'), \PHPCR\PropertyType::STRING);
        $user->setProperty('phpcr_locale:en-topic', 'english', \PHPCR\PropertyType::STRING);
        $user->setProperty('phpcr_locale:ennullfields', array('text', 'settings'), \PHPCR\PropertyType::STRING);
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

        $this->dm->clear();

        $child = $this->dm->findTranslation($this->type, '/functional/thename/child', 'fr');

        $this->assertEquals('fr', $child->locale);
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

        $related = new Article();
        $related->id = '/functional/thename/related';
        $related->author = 'John Doe';
        $related->text = 'Lorem ipsum...';
        $related->topic = 'Interesting Topic';
        $this->dm->persist($related);
        $this->dm->bindTranslation($related, 'en');
        $related->topic = 'Sujet interessant';
        $this->dm->bindTranslation($related, 'fr');

        $child->relatedArticles[] = $related;

        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->findTranslation($this->type, '/functional/thename', 'fr');

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Proxy\Proxy', $doc->child);
        $this->assertEquals('fr', $doc->locale);
        $this->assertEquals('fr', $doc->child->locale);
        $this->assertEquals('fr', $doc->child->relatedArticles[0]->locale);
        $this->assertEquals('Sujet interessant', $doc->child->topic);

        $this->dm->clear();

        $doc = $this->dm->findTranslation($this->type, '/functional/thename', 'en');

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Proxy\Proxy', $doc->child);
        $this->assertEquals('en', $doc->locale);
        $this->assertEquals('en', $doc->child->locale);
        $this->assertEquals('Interesting Topic', $doc->child->topic);

        $this->dm->removeTranslation($doc->child->relatedArticles[0], 'en');
        $this->dm->removeTranslation($doc, 'en');

        $this->dm->flush();
        $this->dm->clear();

        $child = $this->dm->findTranslation($this->type, '/functional/thename/child', 'en');

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Proxy\Proxy', $child->parent);
        $this->assertEquals('en', $child->locale);
        $this->assertEquals('french', $child->parent->topic);
        $this->assertEquals('fr', $child->parent->locale);
        $this->assertEquals('fr', $child->relatedArticles[0]->locale);
    }

    function testBindTranslationLocale()
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

        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->findTranslation($this->type, '/functional/thename', 'fr');

        $this->dm->bindTranslation($doc, 'en');
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Proxy\Proxy', $doc->child);
        $this->assertEquals('en', $doc->locale);
        $this->assertEquals('fr', $doc->child->locale);
        $this->assertEquals('Sujet interessant', $doc->child->topic);
    }
}