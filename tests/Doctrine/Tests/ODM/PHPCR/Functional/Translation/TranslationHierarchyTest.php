<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Translation;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;
use Doctrine\Tests\Models\Translation\Article;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;

/**
 * @group functional
 */
class TranslationHierarchyTest extends PHPCRFunctionalTestCase
{
    private DocumentManager $dm;

    /**
     * Class name of the document class.
     */
    private string $type = Article::class;

    private NodeInterface $node;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager();
        $this->dm->setLocaleChooserStrategy(new LocaleChooser(['en' => ['fr'], 'fr' => ['en']], 'en'));
        $this->node = $this->resetFunctionalNode($this->dm);
        $user = $this->node->addNode('thename');
        $user->setProperty('phpcr:class', $this->type, PropertyType::STRING);
        $user->setProperty('phpcr_locale:fr-topic', 'french', PropertyType::STRING);
        $user->setProperty('phpcr_locale:fr-text', 'french text', PropertyType::STRING);
        $user->setProperty('phpcr_locale:frnullfields', ['nullable'], PropertyType::STRING);
        $user->setProperty('phpcr_locale:en-topic', 'english', PropertyType::STRING);
        $user->setProperty('phpcr_locale:en-text', 'english text', PropertyType::STRING);
        $user->setProperty('phpcr_locale:ennullfields', ['nullable'], PropertyType::STRING);
        $this->dm->getPhpcrSession()->save();
    }

    public function testFind(): void
    {
        $doc = $this->dm->find($this->type, '/functional/thename');

        $this->assertInstanceOf($this->type, $doc);
        $this->assertEquals('/functional/thename', $doc->id);
        $this->assertEquals('thename', $doc->nodename);

        $this->assertNotNull($doc->parent);
        $this->assertEquals('/functional', $doc->parent->getId());
    }

    public function testBindTranslation(): void
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

    public function testFindTranslationPropagateLocale(): void
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

        // we find document "thename" with a specific locale. the child proxy
        // object must be the same locale
        $doc = $this->dm->findTranslation($this->type, '/functional/thename', 'fr');

        $this->assertInstanceOf(Proxy::class, $doc->child);
        $this->assertInstanceOf(Article::class, $doc->child);
        $this->assertEquals('fr', $doc->locale);
        $this->assertEquals('fr', $doc->child->locale);
        $this->assertEquals('fr', $doc->child->relatedArticles[0]->locale);
        $this->assertEquals('Sujet interessant', $doc->child->topic);

        $this->dm->clear();

        $doc = $this->dm->findTranslation($this->type, '/functional/thename', 'en');

        $this->assertInstanceOf(Proxy::class, $doc->child);
        $this->assertInstanceOf(Article::class, $doc->child);
        $this->assertEquals('en', $doc->locale);
        $this->assertEquals('en', $doc->child->locale);
        $this->assertEquals('Interesting Topic', $doc->child->topic);

        $this->dm->removeTranslation($doc->child->relatedArticles[0], 'en');
        $this->dm->removeTranslation($doc, 'en');

        $this->dm->flush();
        $this->dm->clear();

        // if we remove the english translation from the doc and the related, loading the child
        // should give the doc and the related in french
        $child = $this->dm->findTranslation($this->type, '/functional/thename/child', 'en');

        $this->assertInstanceOf(Proxy::class, $child->parent);
        $this->assertInstanceOf(Article::class, $child->parent);
        $this->assertEquals('en', $child->locale);

        $this->assertEquals('fr', $child->parent->locale);
        $this->assertEquals('french', $child->parent->topic);
        $this->assertEquals('fr', $child->relatedArticles[0]->locale);
    }

    public function testBindTranslationLocale(): void
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

        $this->assertInstanceOf(Proxy::class, $doc->child);
        $this->assertInstanceOf(Article::class, $doc->child);
        $this->assertEquals('fr', $doc->locale);
        $this->assertEquals('fr', $doc->child->locale);
        $this->assertEquals('Sujet interessant', $doc->child->topic);
    }

    public function testRefreshProxyUsesFallback(): void
    {
        $parent = new ParentObj();
        $parent->id = '/functional/thename/child';
        $this->dm->persist($parent);

        $child = new ChildObj();
        $child->parent = $parent;
        $child->name = 'c1';
        $child->text = 'french';
        $this->dm->persist($child);
        $this->dm->bindTranslation($child, 'fr');

        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->findTranslation(null, '/functional/thename/child', 'fr');
        $this->assertEquals('french', $doc->children['c1']->text);

        $this->dm->clear();

        $doc = $this->dm->findDocument('/functional/thename');

        $this->assertInstanceOf(ParentObj::class, $doc->child);
        $this->assertEquals('french', $doc->child->children['c1']->text);
    }
}

#[PHPCR\Document(translator: 'child', referenceable: true)]
class ParentObj
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Locale]
    public $locale;

    #[PHPCR\Nodename]
    public $name;

    #[PHPCR\ParentDocument]
    public $parent;

    #[PHPCR\Children(cascade: 'all')]
    public $children;
}

#[PHPCR\Document(translator: 'child', referenceable: true)]
class ChildObj
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Locale]
    public $locale;

    #[PHPCR\Nodename]
    public $name;

    #[PHPCR\ParentDocument]
    public $parent;

    #[PHPCR\Field(type: 'string', translated: true)]
    public $text;
}
