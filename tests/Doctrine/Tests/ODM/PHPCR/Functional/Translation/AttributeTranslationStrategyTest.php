<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Translation;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory,
    Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy;

use Doctrine\Tests\Models\Translation\Article;
use Doctrine\ODM\PHPCR\Translation\Translation;

class AttributeTranslationStrategyTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    protected $testNodeName = '__test-node__';

    public function setup()
    {
        $this->dm = $this->createDocumentManager();
        $this->session = $this->dm->getPhpcrSession();
        $this->workspace = $this->dm->getPhpcrSession()->getWorkspace();
        $this->metadata = $this->dm->getClassMetadata('Doctrine\Tests\Models\Translation\Article');
    }

    public function tearDown()
    {
        $this->removeTestNode();
    }

    public function testSaveTranslation()
    {
        // First save some translations
        $doc = new Article();
        $doc->author = 'John Doe';
        $doc->topic = 'Some interesting subject';
        $doc->text = 'Lorem ipsum...';

        $node = $this->getTestNode();

        $strategy = new AttributeTranslationStrategy();
        $strategy->saveTranslation($doc, $node, $this->metadata, 'en');

        // The document locale was not yet assigned so it must be set
        $this->assertEquals('en', $doc->locale);

        // Save translation in another language

        $doc->topic = 'Un sujet intéressant';

        $strategy->saveTranslation($doc, $node, $this->metadata, 'fr');
        $this->dm->flush();

        // The document locale was already set to it must not change
        $this->assertEquals('en', $doc->locale);

        // Then test we have what we expect in the content repository
        $node = $this->session->getNode('/' . $this->testNodeName);

        $this->assertTrue($node->hasProperty(self::propertyNameForLocale('en', 'topic')));
        $this->assertTrue($node->hasProperty(self::propertyNameForLocale('fr', 'topic')));
        $this->assertTrue($node->hasProperty(self::propertyNameForLocale('en', 'text')));
        $this->assertTrue($node->hasProperty(self::propertyNameForLocale('fr', 'text')));
        $this->assertFalse($node->hasProperty(self::propertyNameForLocale('fr', 'author')));
        $this->assertFalse($node->hasProperty(self::propertyNameForLocale('en', 'author')));

        $this->assertEquals('Some interesting subject', $node->getPropertyValue(self::propertyNameForLocale('en', 'topic')));
        $this->assertEquals('Un sujet intéressant', $node->getPropertyValue(self::propertyNameForLocale('fr', 'topic')));
        $this->assertEquals('Lorem ipsum...', $node->getPropertyValue(self::propertyNameForLocale('en', 'text')));
        $this->assertEquals('Lorem ipsum...', $node->getPropertyValue(self::propertyNameForLocale('fr', 'text')));
    }

    public function testLoadTranslation()
    {
        // Create the node in the content repository
        $node = $this->getTestNode();
        $node->setProperty(self::propertyNameForLocale('en', 'topic'), 'English topic');
        $node->setProperty(self::propertyNameForLocale('en', 'text'), 'English text');
        $node->setProperty(self::propertyNameForLocale('fr', 'topic'), 'Sujet français');
        $node->setProperty(self::propertyNameForLocale('fr', 'text'), 'Texte français');
        $node->setProperty('author', 'John Doe');

        $this->session->save();

        // Then try to read it's translation
        $doc = new Article();

        $strategy = new AttributeTranslationStrategy();
        $strategy->loadTranslation($doc, $node, $this->metadata, 'en');

        // And check the translatable properties have the correct value
        $this->assertEquals('English topic', $doc->topic);
        $this->assertEquals('English text', $doc->text);

        // Load another language and test the document has been updated
        $strategy->loadTranslation($doc, $node, $this->metadata, 'fr');

        $this->assertEquals('Sujet français', $doc->topic);
        $this->assertEquals('Texte français', $doc->text);
    }

    public function testRemoveTranslation()
    {
        // First save some translations
        $doc = new Article();
        $doc->author = 'John Doe';
        $doc->topic = 'Some interesting subject';
        $doc->text = 'Lorem ipsum...';

        $node = $this->getTestNode();

        $strategy = new AttributeTranslationStrategy();
        $strategy->saveTranslation($doc, $node, $this->metadata, 'en');
        $doc->topic = 'sujet interessant';
        $strategy->saveTranslation($doc, $node, $this->metadata, 'fr');

        $this->assertTrue($node->hasProperty(self::propertyNameForLocale('en', 'topic')));
        $this->assertTrue($node->hasProperty(self::propertyNameForLocale('en', 'text')));

        // Then remove the french translation
        $strategy->removeTranslation($doc, $node, $this->metadata, 'fr');

        $this->assertFalse($node->hasProperty(self::propertyNameForLocale('fr', 'topic')));
        $this->assertFalse($node->hasProperty(self::propertyNameForLocale('fr', 'text')));
        $this->assertTrue($node->hasProperty(self::propertyNameForLocale('en', 'topic')));
        $this->assertTrue($node->hasProperty(self::propertyNameForLocale('en', 'text')));
    }

    public function testGetLocaleFor()
    {
        $node = $this->getTestNode();
        $node->setProperty(self::propertyNameForLocale('en', 'topic'), 'English topic');
        $node->setProperty(self::propertyNameForLocale('en', 'text'), 'English text');
        $node->setProperty(self::propertyNameForLocale('fr', 'topic'), 'Sujet français');
        $node->setProperty(self::propertyNameForLocale('fr', 'text'), 'Texte français');
        $node->setProperty(self::propertyNameForLocale('de', 'topic'), 'Deutche Betreff');
        $node->setProperty(self::propertyNameForLocale('de', 'text'), 'Deutche Texte');
        $this->session->save();

        $doc = new Article();

        $strategy = new AttributeTranslationStrategy();
        $locales = $strategy->getLocalesFor($doc, $node, $this->metadata);

        $this->assertTrue(is_array($locales));
        $this->assertEquals(3, count($locales));
        $this->assertContains('fr', $locales);
        $this->assertContains('en', $locales);
        $this->assertContains('de', $locales);
    }

    protected function getTestNode()
    {
        $this->removeTestNode();
        $node = $this->session->getRootNode()->addNode($this->testNodeName);
        $this->session->save();

        $this->dm->clear();
        return $node;
    }

    protected function removeTestNode()
    {
        $root = $this->session->getRootNode();
        if ($root->hasNode($this->testNodeName)) {
            $root->getNode($this->testNodeName)->remove();
            $this->session->save();
        }
    }

    static function propertyNameForLocale($locale, $property)
    {
        return Translation::LOCALE_NAMESPACE . ':' . $locale . '-' . $property;
    }

}
