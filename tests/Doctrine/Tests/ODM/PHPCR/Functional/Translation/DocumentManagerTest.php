<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Translation;

use Doctrine\Tests\Models\Translation\Article;
use Doctrine\Tests\Models\Translation\Comment;
use Doctrine\Tests\Models\Translation\InvalidMapping;
use Doctrine\Tests\Models\Translation\DerivedArticle;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;
use Doctrine\ODM\PHPCR\PHPCRException;

class DocumentManagerTest extends PHPCRFunctionalTestCase
{
    protected $testNodeName = '__my_test_node__';

    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    protected $dm;

    /**
     * @var \PHPCR\SessionInterface
     */
    protected $session;

    /**
     * @var \PHPCR\NodeInterface
     */
    protected $node;

    /**
     * @var ClassMetadata
     */
    protected $metadata;

    /**
     * @var Article
     */
    protected $doc;

    /**
     * @var string
     */
    protected $class = 'Doctrine\Tests\Models\Translation\Article';

    /**
     * @var string
     */
    protected $childrenClass = 'Doctrine\Tests\Models\Translation\Comment';

    protected $localePrefs = array(
        'en' => array('de', 'fr'),
        'fr' => array('de', 'en'),
        'de' => array('en'),
        'it' => array('fr', 'de', 'en'),
    );

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->dm->setLocaleChooserStrategy(new LocaleChooser($this->localePrefs, 'en'));
        $this->node = $this->resetFunctionalNode($this->dm);
        $this->dm->clear();

        $this->session = $this->dm->getPhpcrSession();
        $this->metadata = $this->dm->getClassMetadata($this->class);

        $this->doc = new Article();
        $this->doc->id = '/functional/' . $this->testNodeName;
        $this->doc->author = 'John Doe';
        $this->doc->topic = 'Some interesting subject';
        $this->doc->setText('Lorem ipsum...');
        $this->doc->setSettings(array());
        $this->doc->assoc = array('key' => 'value');
    }

    protected function getTestNode()
    {
        return $this->session->getNode('/functional/'.$this->testNodeName);
    }

    protected function assertDocumentStored()
    {
        $node = $this->getTestNode();
        $this->assertNotNull($node);

        $this->assertFalse($node->hasProperty('topic'));
        $this->assertTrue($node->hasProperty(AttributeTranslationStrategyTest::propertyNameForLocale('en', 'topic')));
        $this->assertEquals('Some interesting subject', $node->getPropertyValue(AttributeTranslationStrategyTest::propertyNameForLocale('en', 'topic')));
        $this->assertTrue($node->hasProperty(AttributeTranslationStrategyTest::propertyNameForLocale('fr', 'topic')));
        $this->assertEquals('Un sujet intéressant', $node->getPropertyValue(AttributeTranslationStrategyTest::propertyNameForLocale('fr', 'topic')));

        $this->assertEquals('fr', $this->doc->locale);
    }

    public function testPersistNew()
    {
        $this->dm->persist($this->doc);
        $this->dm->flush();

        // Assert the node exists
        $node = $this->getTestNode();
        $this->assertNotNull($node);

        $this->assertFalse($node->hasProperty('topic'));

        $this->assertTrue($node->hasProperty(AttributeTranslationStrategyTest::propertyNameForLocale('en', 'topic')));
        $this->assertEquals('Some interesting subject', $node->getPropertyValue(AttributeTranslationStrategyTest::propertyNameForLocale('en', 'topic')));

        $this->assertTrue($node->hasProperty('author'));
        $this->assertEquals('John Doe', $node->getPropertyValue('author'));

        $this->assertEquals('en', $this->doc->locale);
    }

    public function testBindTranslation()
    {
        $this->dm->persist($this->doc);
        $this->dm->bindTranslation($this->doc, 'en');
        $this->dm->flush();

        $this->doc->topic = 'Un sujet intéressant';
        $this->dm->bindTranslation($this->doc, 'fr');
        $this->dm->flush();

        $this->assertDocumentStored();
    }

    public function testRemoveTranslation()
    {
        $this->dm->persist($this->doc);
        $this->dm->bindTranslation($this->doc, 'en');
        $this->dm->flush();

        $this->assertEquals(array('en'), $this->dm->getLocalesFor($this->doc));

        $this->doc->topic = 'Un sujet intéressant';
        $this->dm->bindTranslation($this->doc, 'fr');
        $this->dm->flush();

        $this->dm->clear();
        $this->doc = $this->dm->findTranslation(null, $this->doc->id, 'fr');

        $this->assertEquals(array('en', 'fr'), $this->dm->getLocalesFor($this->doc));

        $this->dm->removeTranslation($this->doc, 'en');
        $this->assertEquals(array('fr'), $this->dm->getLocalesFor($this->doc));
        $this->dm->clear();

        $this->doc = $this->dm->findTranslation(null, $this->doc->id, 'fr');
        $this->assertEquals(array('en', 'fr'), $this->dm->getLocalesFor($this->doc));
        $this->dm->removeTranslation($this->doc, 'en');

        $this->dm->flush();
        $this->assertEquals(array('fr'), $this->dm->getLocalesFor($this->doc));

        $this->dm->clear();
        $this->doc = $this->dm->find(null, $this->doc->id);

        $this->assertEquals(array('fr'), $this->dm->getLocalesFor($this->doc));

        try {
            $this->dm->removeTranslation($this->doc, 'fr');
            $this->fail('Last translation should not be removable');
        } catch (PHPCRException $e) {

        }
    }

    /**
     * find translation in non-default language and then save it back has to keep language
     */
    public function testFindTranslationAndUpdate()
    {
        $this->dm->persist($this->doc);
        $this->dm->bindTranslation($this->doc, 'en');
        $this->dm->flush();

        $this->doc->topic = 'Un intéressant';
        $this->dm->bindTranslation($this->doc, 'fr');
        $this->dm->flush();
        $this->dm->clear();

        $node = $this->getTestNode();
        $this->assertNotNull($node);

        $this->assertEquals('Un intéressant', $node->getPropertyValue(AttributeTranslationStrategyTest::propertyNameForLocale('fr', 'topic')));

        $this->doc = $this->dm->findTranslation(null, '/functional/' . $this->testNodeName, 'fr');
        $this->doc->topic = 'Un sujet intéressant';
        $this->dm->flush();

        $this->assertDocumentStored();
    }

    /**
     * changing the locale and flushing should pick up changes automatically
     */
    public function testUpdateLocaleAndFlush()
    {
        $this->dm->persist($this->doc);
        $this->dm->bindTranslation($this->doc, 'en');
        $this->dm->flush();

        $this->doc->topic = 'Un sujet intéressant';
        $this->doc->locale = 'fr';
        $this->dm->flush();

        $this->assertDocumentStored();

        // Get de translation via fallback en
        $this->doc = $this->dm->findTranslation(null, '/functional/' . $this->testNodeName, 'de');
        $this->doc->topic = 'Ein interessantes Thema';

        //set locale explicitly
        $this->doc->locale = 'de';
        $this->dm->flush();

        $node = $this->getTestNode();

        // ensure the new translation was bound and persisted
        $this->assertEquals('Ein interessantes Thema', $node->getPropertyValue(AttributeTranslationStrategyTest::propertyNameForLocale('de', 'topic')));
    }

    public function testFlush()
    {
        $this->dm->persist($this->doc);

        $this->doc->topic = 'Un sujet intéressant';

        $this->dm->bindTranslation($this->doc, 'fr');
        $this->dm->flush();

        $this->doc->topic = 'Un autre sujet';
        $this->dm->flush();

        $node = $this->getTestNode();
        $this->assertNotNull($node);

        $this->assertTrue($node->hasProperty(AttributeTranslationStrategyTest::propertyNameForLocale('fr', 'topic')));
        $this->assertEquals('Un autre sujet', $node->getPropertyValue(AttributeTranslationStrategyTest::propertyNameForLocale('fr', 'topic')));

        $this->assertEquals('fr', $this->doc->locale);
    }

    public function testFind()
    {
        $this->dm->persist($this->doc);
        $this->dm->flush();

        $this->doc = $this->dm->find($this->class, '/functional/' . $this->testNodeName);

        $this->assertNotNull($this->doc);
        $this->assertEquals('en', $this->doc->locale);

        $node = $this->getTestNode();
        $this->assertNotNull($node);
        $this->assertTrue($node->hasProperty(AttributeTranslationStrategyTest::propertyNameForLocale('en', 'topic')));
        $this->assertEquals('Some interesting subject', $node->getPropertyValue(AttributeTranslationStrategyTest::propertyNameForLocale('en', 'topic')));
    }

    public function testFindTranslation()
    {
        $this->doc->topic = 'Un autre sujet';
        $this->doc->locale = 'fr';
        $this->dm->persist($this->doc);
        $this->dm->flush();

        $this->doc = $this->dm->findTranslation($this->class, '/functional/' . $this->testNodeName, 'fr');

        $this->assertNotNull($this->doc);
        $this->assertEquals('fr', $this->doc->locale);
        $this->assertEquals('Un autre sujet', $this->doc->topic);
    }

    /**
     * Test that children are retrieved in the parent locale
     */
    public function testFindTranslationWithChildren()
    {
        $this->dm->persist($this->doc);
        $this->dm->bindTranslation($this->doc, 'en');

        $comment = new Comment();
        $comment->name = 'new-comment';
        $comment->parent = $this->doc;
        $this->dm->persist($comment);

        $comment->setText('This is a great article');
        $this->dm->bindTranslation($comment, 'en');
        $comment->setText('Très bon article');
        $this->dm->bindTranslation($comment, 'fr');
        $this->dm->flush();

        $this->dm->clear();

        $this->doc = $this->dm->findTranslation($this->class, '/functional/' . $this->testNodeName, 'fr');
        $this->assertEquals('en', $this->doc->locale);
        $children = $this->doc->getChildren();

        foreach ($children as $comment) {
            $this->assertEquals('fr', $comment->locale);
            $this->assertEquals('Très bon article', $comment->getText());
        }

        $this->doc->topic = 'Un autre sujet';
        $this->dm->bindTranslation($this->doc, 'fr');
        $this->dm->flush();

        $this->doc = $this->dm->findTranslation($this->class, '/functional/' . $this->testNodeName, 'fr');
        $this->assertEquals('fr', $this->doc->locale);
        $children = $this->doc->getChildren();
        $this->assertCount(1, $children);
        foreach ($children as $comment) {
            $this->assertEquals('fr', $comment->locale);
            $this->assertEquals('Très bon article', $comment->getText());
        }
        $children = $this->dm->getChildren($this->doc);
        $this->assertCount(1, $children);
        foreach ($children as $comment) {
            $this->assertEquals('fr', $comment->locale);
            $this->assertEquals('Très bon article', $comment->getText());
        }

        $this->metadata->mappings['children']['cascade'] = ClassMetadata::CASCADE_TRANSLATION;

        $this->doc = $this->dm->findTranslation($this->class, '/functional/' . $this->testNodeName, 'en');
        $this->assertEquals('en', $this->doc->locale);
        $children = $this->doc->getChildren();
        $this->assertCount(1, $children);
        foreach ($children as $comment) {
            $this->assertEquals('en', $comment->locale);
            $this->assertEquals('This is a great article', $comment->getText());
        }
        $children = $this->dm->getChildren($this->doc);
        $this->assertCount(1, $children);
        foreach ($children as $comment) {
            $this->assertEquals('en', $comment->locale);
            $this->assertEquals('This is a great article', $comment->getText());
        }
    }

    /**
     * Test that children are retrieved in the parent locale
     */
    public function testFindTranslationWithUntranslatedChildren()
    {
        $this->dm->persist($this->doc);
        $this->dm->bindTranslation($this->doc, 'en');

        $this->doc->topic = 'Un autre sujet';
        $this->dm->bindTranslation($this->doc, 'fr');
        $this->dm->flush();

        $testNode = $this->node->getNode($this->testNodeName);
        $testNode->addNode('new-comment');
        $this->session->save();

        $this->dm->clear();

        $this->doc = $this->dm->findTranslation($this->class, '/functional/' . $this->testNodeName, 'fr');
        $this->assertEquals('fr', $this->doc->locale);
        $children = $this->doc->getChildren();
        $this->assertCount(1, $children);
        foreach ($children as $comment) {
            $this->assertInstanceOf('Doctrine\ODM\PHPCR\Document\Generic', $comment);
            $this->assertNull($this->dm->getUnitOfWork()->getCurrentLocale($comment));
        }

        $this->dm->clear();

        $this->doc = $this->dm->findTranslation($this->class, '/functional/' . $this->testNodeName, 'en');
        $children = $this->dm->getChildren($this->doc);
        $this->assertCount(1, $children);
        foreach ($children as $comment) {
            $this->assertInstanceOf('Doctrine\ODM\PHPCR\Document\Generic', $comment);
            $this->assertNull($this->dm->getUnitOfWork()->getCurrentLocale($comment));
        }
    }

    public function testFindByUUID()
    {
        $this->doc->topic = 'Un autre sujet';
        $this->doc->locale = 'fr';
        $this->dm->persist($this->doc);
        $this->dm->flush();

        $node = $this->session->getNode('/functional/'.$this->testNodeName);
        $node->addMixin('mix:referenceable');
        $this->session->save();

        $this->document = $this->dm->findTranslation($this->class, $node->getIdentifier(), 'fr');
        $this->assertInstanceOf($this->class, $this->document);
    }

    /**
     * Italian translation does not exist so as defined in $this->localePrefs we
     * will get french as it has higher priority than english
     */
    public function testFindWithLanguageFallback()
    {
        $this->dm->persist($this->doc);
        $this->doc->topic = 'Un autre sujet';
        $this->doc->text = 'Text';
        $this->doc->locale = 'fr';
        $this->dm->flush();
        $this->dm->getLocaleChooserStrategy()->setLocale('it');
        $this->doc = $this->dm->find($this->class, '/functional/' . $this->testNodeName);

        $this->assertNotNull($this->doc);
        $this->assertEquals('fr', $this->doc->locale);
        $this->assertEquals('Un autre sujet', $this->doc->topic);
        $this->assertEquals('Text', $this->doc->text);
    }

    /**
     * Same as findWithLanguageFallback, but all properties are nullable.
     */
    public function testFindWithLanguageFallbackNullable()
    {
        $doc = new Comment();
        $doc->id = '/functional/fallback-nullable';
        $doc->setText('Un commentaire');
        $doc->locale = 'fr';
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $this->dm->getLocaleChooserStrategy()->setLocale('it');
        $doc = $this->dm->find(null, '/functional/fallback-nullable');

        $this->assertNotNull($doc);
        $this->assertEquals('fr', $doc->locale);
        $this->assertEquals('Un commentaire', $doc->getText());
    }

    /**
     * Italian translation does not exist so as defined in $this->localePrefs we
     * will get french as it has higher priority than english
     */
    public function testFindTranslationWithLanguageFallback()
    {
        $this->dm->persist($this->doc);
        $this->doc->topic = 'Un autre sujet';
        $this->doc->locale = 'fr';
        $this->dm->flush();

        $this->doc = $this->dm->findTranslation($this->class, '/functional/' . $this->testNodeName, 'it');

        $this->assertNotNull($this->doc);
        $this->assertEquals('fr', $this->doc->locale);
        $this->assertEquals('Un autre sujet', $this->doc->topic);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Translation\MissingTranslationException
     */
    public function testFindTranslationWithInvalidLanguageFallback()
    {
        $this->dm->persist($this->doc);
        $this->dm->flush();

        $this->dm->findTranslation($this->class, '/functional/' . $this->testNodeName, 'es');
    }

    /**
     * Italian translation does not exist so as defined in $this->localePrefs we
     * will get french as it has higher priority than english
     */
    public function testFindTranslationNoFallback()
    {
        $this->dm->persist($this->doc);
        $this->dm->flush();
        // if we do not flush, the translation node does not exist

        $doc = $this->dm->findTranslation($this->class, $this->doc->id, 'it', true);
        $this->assertInstanceOf('Doctrine\Tests\Models\Translation\Article', $doc);
        $this->assertEquals('John Doe', $doc->author);
        $this->assertEquals('en', $doc->locale);

        $this->setExpectedException('Doctrine\ODM\PHPCR\Translation\MissingTranslationException');
        $this->dm->findTranslation($this->class, '/functional/' . $this->testNodeName, 'it', false);
    }

    /**
     * Test what happens if all document fields are nullable and actually null.
     */
    public function testTranslationOnlyNullProperties()
    {
        $path = $this->node->getPath() . '/only-null';
        $doc = new Comment();
        $doc->id = $path;
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(null, $path);
        $this->assertInstanceOf('Doctrine\Tests\Models\Translation\Comment', $doc);
        $this->assertNull($doc->getText());
    }

    /**
     * We only validate when saving, never when loading. This has to find the
     * incomplete english translation.
     */
    public function testFindNullableFieldIncomplete()
    {
        $node = $this->node->addNode('find');
        $node->setProperty('phpcr:class', 'Doctrine\Tests\Models\Translation\Article');
        $node->setProperty(AttributeTranslationStrategyTest::propertyNameForLocale('en', 'topic'), 'title');
        $node->setProperty(AttributeTranslationStrategyTest::propertyNameForLocale('de', 'topic'), 'Titel');

        $this->dm->getPhpcrSession()->save();
        $this->dm->clear();

        /** @var $doc Article */
        $doc = $this->dm->find(null, $this->node->getPath().'/find');

        $this->assertEquals('en', $doc->locale);
        $this->assertEquals('title', $doc->topic);
        $this->assertNull($doc->text);
    }

    /**
     * No translation whatsoever is available. All translated fields have to be
     * null as we do not validate on loading.
     */
    public function testFindNullableFieldNone()
    {
        $node = $this->node->addNode('find');
        $node->setProperty('phpcr:class', 'Doctrine\Tests\Models\Translation\Article');

        $this->dm->getPhpcrSession()->save();
        $this->dm->clear();

        /** @var $doc Article */
        $doc = $this->dm->find(null, $this->node->getPath().'/find');

        $this->assertEquals('en', $doc->locale);
        $this->assertNull($doc->topic);
        $this->assertNull($doc->text);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testFlushNullableFieldNotSetInsert()
    {
        $doc = new Article();
        $doc->id = $this->node->getPath().'/flush';
        $doc->topic = 'title';
        $doc->locale = 'en';
        $this->dm->persist($doc);

        $this->dm->flush();
    }

    public function testFlushNullableFieldNotSetUpdate()
    {
        $doc = new Article();
        $doc->id = $this->node->getPath().'/flush';
        $doc->topic = 'title';
        $doc->text = 'text';
        $doc->locale = 'en';
        $this->dm->persist($doc);

        $this->dm->flush();

        $this->setExpectedException('Doctrine\ODM\PHPCR\PHPCRException');

        $doc->topic = null;
        $this->dm->flush();
    }

    public function testGetLocaleFor()
    {
        // Only 1 language is persisted
        $this->dm->persist($this->doc);
        $this->dm->flush();

        $locales = $this->dm->getLocalesFor($this->doc);
        $this->assertEquals(array('en'), $locales);

        // A second language is persisted
        $this->dm->bindTranslation($this->doc, 'fr');
        // Check that french is now also returned even without having flushed
        $locales = $this->dm->getLocalesFor($this->doc);
        $this->assertCount(2, $locales);
        $this->assertTrue(in_array('en', $locales));
        $this->assertTrue(in_array('fr', $locales));

        $this->dm->flush();

        $locales = $this->dm->getLocalesFor($this->doc);
        $this->assertCount(2, $locales);
        $this->assertTrue(in_array('en', $locales));
        $this->assertTrue(in_array('fr', $locales));

        // A third language is bound but not yet flushed
        $this->dm->bindTranslation($this->doc, 'de');

        $locales = $this->dm->getLocalesFor($this->doc);
        $this->assertCount(3, $locales);
        $this->assertTrue(in_array('en', $locales));
        $this->assertTrue(in_array('fr', $locales));
        $this->assertTrue(in_array('de', $locales));
    }

    public function testRemove()
    {
        $this->dm->persist($this->doc);
        $this->dm->bindTranslation($this->doc, 'en');
        $this->dm->bindTranslation($this->doc, 'fr');
        $this->dm->flush();

        $this->dm->remove($this->doc);
        $this->dm->flush();
        $this->dm->clear();

        $this->doc = $this->dm->find($this->class, '/functional/' . $this->testNodeName);
        $this->assertNull($this->doc, 'Document must be null after deletion');

        $this->doc = new Article();
        $this->doc->id = '/functional/' . $this->testNodeName;
        $this->doc->author = 'John Doe';
        $this->doc->topic = 'Some interesting subject';
        $this->doc->setText('Lorem ipsum...');
        $this->dm->persist($this->doc);
        $this->dm->bindTranslation($this->doc, 'en');
        $this->dm->flush();

        $locales = $this->dm->getLocalesFor($this->doc);
        $this->assertEquals(array('en'), $locales, 'Removing a document must remove all translations');
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testInvalidTranslationStrategy()
    {
        $this->doc = new InvalidMapping();
        $this->doc->id = '/functional/' . $this->testNodeName;
        $this->doc->topic = 'foo';
        $this->dm->persist($this->doc);
        $this->dm->bindTranslation($this->doc, 'en');
        $this->dm->flush();
    }

    /**
     * bindTranslation with a document that is not persisted should fail
     *
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     */
    public function testBindTranslationWithoutPersist()
    {
        $this->doc = new CmsArticle();
        $this->doc->id = '/functional/' . $this->testNodeName;
        $this->dm->bindTranslation($this->doc, 'en');
    }

    /**
     * bindTranslation with a document that is not translatable should fail
     *
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testBindTranslationNonTranslatable()
    {
        $this->doc = new CmsArticle();
        $this->doc->id = '/functional/' . $this->testNodeName;
        $this->dm->persist($this->doc);
        $this->dm->bindTranslation($this->doc, 'en');
    }

    /**
     * bindTranslation with a document inheriting from a translatable document
     * should not fail
     */
    public function testBindTranslationInherited()
    {
        $this->doc = new DerivedArticle();
        $this->doc->id = '/functional/' . $this->testNodeName;
        $this->dm->persist($this->doc);
        $this->dm->bindTranslation($this->doc, 'en');
        $this->assertEquals('en', $this->doc->locale);
    }

    public function testFindTranslationNonPersisted()
    {
        $a = new Article();
        $a->id = '/functional/' . $this->testNodeName;
        $a->title = 'Hello';
        $this->dm->persist($a);

        $translations = array(
            'en' => array('topic' => 'Welcome', 'assoc_value' => 'in en'),
            'fr' => array('topic' => 'Bienvenue', 'assoc_value' => 'in fr'),
            'de' => array('topic' => 'Wilkommen',  'assoc_value' => 'in de'),
        );

        foreach ($translations as $locale => $values) {
            $a->topic = $values['topic'];
            $a->assoc = array('key' => $values['assoc_value']);
            $this->dm->bindTranslation($a, $locale);
        }

        foreach ($translations as $locale => $values) {
            $trans = $this->dm->findTranslation(
                'Doctrine\Tests\Models\Translation\Article',
                '/functional/' . $this->testNodeName,
                $locale
            );

            $this->assertNotNull($trans, 'Finding translation with locale "'.$locale.'"');
            $this->assertInstanceOf('Doctrine\Tests\Models\Translation\Article', $trans);
            $this->assertEquals($values['topic'], $trans->topic);
            $this->assertEquals($values['assoc_value'], $trans->assoc['key']);
            $this->assertEquals($locale, $trans->locale);
        }

        $locales = $this->dm->getLocalesFor($a);
        $this->assertEquals(array('en', 'fr', 'de'), $locales);
    }

    /**
     * When loading the "it" locale, the fallback is "de" and we have a not yet
     * flushed translation for that.
     */
    public function testFindTranslationNonPersistedFallback()
    {
        $a = new Article();
        $a->id = '/functional/' . $this->testNodeName;
        $a->topic = 'Hello';
        $a->text = 'Some text';
        $this->dm->persist($a);
        $this->dm->flush();

        $a->topic = 'Guten tag';
        $this->dm->bindTranslation($a, 'de');

        // find the italian translation
        $trans = $this->dm->findTranslation(
            null,
            '/functional/' . $this->testNodeName,
            'it'
        );

        $this->assertNotNull($trans);
        $this->assertInstanceOf('Doctrine\Tests\Models\Translation\Article', $trans);
        $this->assertEquals('Guten tag', $trans->topic);
        $this->assertEquals('de', $trans->locale);
    }

    /*
     * A series of edge cases:
     *
     * We already have a translation for a locale (de), load the document in a
     * different locale (en) and set some value, then try to bind it in the
     * first locale (de). This would result in overwriting fields of the german
     * translation.
     *
     * Rather than allow this confusing behavior we throw an exception.
     */

    /**
     * The locale is only in memory.
     *
     * @expectedException \Doctrine\ODM\PHPCR\Exception\RuntimeException
     * @expectedExceptionMessage Translation "de" already exists
     */
    public function testBindTranslationMemoryOverwrite()
    {
        $a = new Article();
        $a->id = '/functional/' . $this->testNodeName;
        $a->topic = 'Hello';
        $a->text = 'This is an article in English';
        $this->dm->persist($a);
        $this->dm->flush();

        $a->topic = 'Guten tag';
        $a->text = 'Dies ist ein Artikel Deutsch';
        $this->dm->persist($a);
        $this->dm->bindTranslation($a, 'de');

        $a = $this->dm->findTranslation(null, '/functional/' . $this->testNodeName, 'en');
        $a->topic = 'Hallo';
        // this would kill the $a->text and set it back to the english text
        $this->dm->bindTranslation($a, 'de');
    }

    /**
     * The locale is flushed.
     *
     * @expectedException \Doctrine\ODM\PHPCR\Exception\RuntimeException
     * @expectedExceptionMessage Translation "de" already exists
     */
    public function testBindTranslationFlushedOverwrite()
    {
        $a = new Article();
        $a->id = '/functional/' . $this->testNodeName;
        $a->topic = 'Hello';
        $a->text = 'This is an article in English';
        $this->dm->persist($a);
        $this->dm->flush();

        $a->topic = 'Guten tag';
        $a->text = 'Dies ist ein Artikel Deutsch';
        $this->dm->persist($a);
        $this->dm->bindTranslation($a, 'de');
        $this->dm->flush();

        $a = $this->dm->findTranslation(null, '/functional/' . $this->testNodeName, 'en');
        $a->topic = 'Hallo';
        // this would kill the $a->text and set it back to the english text
        $this->dm->bindTranslation($a, 'de');
    }

    /**
     * The locale is not even currently loaded
     *
     * @expectedException \Doctrine\ODM\PHPCR\Exception\RuntimeException
     * @expectedExceptionMessage Translation "de" already exists
     */
    public function testBindTranslationOverwrite()
    {
        $a = new Article();
        $a->id = '/functional/' . $this->testNodeName;
        $a->topic = 'Hello';
        $a->text = 'This is an article in English';
        $this->dm->persist($a);
        $this->dm->flush();

        $a->topic = 'Guten tag';
        $a->text = 'Dies ist ein Artikel Deutsch';
        $this->dm->persist($a);
        $this->dm->bindTranslation($a, 'de');
        $this->dm->flush();
        $this->dm->clear();

        $a = $this->dm->find(null, '/functional/' . $this->testNodeName);
        $a->topic = 'Hallo';
        // this would kill the $a->text and set it back to the english text
        $this->dm->bindTranslation($a, 'de');
    }

    public function testAssocWithNulls()
    {
        $assoc = array('foo' => 'bar', 'test' => null, 2 => 'huhu');

        $a = new Article();
        $a->id = '/functional/' . $this->testNodeName;
        $a->topic = 'Hello';
        $a->text = 'This is an article in English';
        $a->assoc = $assoc;
        $this->dm->persist($a);
        $this->dm->bindTranslation($a, 'de');
        $this->dm->flush();
        $this->dm->clear();

        $a = $this->dm->find(null, '/functional/' . $this->testNodeName);
        $this->assertEquals($assoc, $a->assoc);
    }

    public function testAdditionalFindCallsDoNotRefresh()
    {
        $a = new Article();
        $a->id = '/functional/' . $this->testNodeName;
        $a->topic = 'Hello';
        $a->text = 'Some text';
        $this->dm->persist($a);
        $this->dm->flush();

        $a->topic = 'Guten tag';

        $trans = $this->dm->findTranslation(
            null,
            '/functional/' . $this->testNodeName,
            'en'
        );

        $this->assertEquals('Guten tag', $trans->topic);
    }
}
