<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Translation;

use Doctrine\Tests\Models\Translation\Article,
    Doctrine\Tests\Models\Translation\InvalidMapping,
    Doctrine\Tests\Models\CMS\CmsArticle,
    Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;

use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy,
    Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;


class DocumentManagerTest extends PHPCRFunctionalTestCase
{
    protected $testNodeName = '__my_test_node__';

    /**
     * @var Doctrine\ODM\PHPCR\DocumentManager
     */
    protected $dm;

    /**
     * @var PHPCR\Session
     */
    protected $session;

    /**
     * @var Doctrine\ODM\PHPCR\Mapping\ClassMetadata
     */
    protected $metadata;

    protected $doc;

    public function setUp()
    {
        $localePrefs = array(
            'en' => array('en', 'de', 'fr'),
            'fr' => array('fr', 'de', 'en'),
            'it' => array('fr', 'de', 'en'),
        );

        $this->dm = $this->createDocumentManager();
        $this->dm->setLocaleChooserStrategy(new LocaleChooser($localePrefs, 'en'));
        $this->resetFunctionalNode($this->dm);
        $this->dm->clear();

        $this->session = $this->dm->getPhpcrSession();
        $this->metadata = $this->dm->getClassMetadata('Doctrine\Tests\Models\Translation\Article');

        $doc = new Article();
        $doc->id = '/functional/' . $this->testNodeName;
        $doc->author = 'John Doe';
        $doc->topic = 'Some interesting subject';
        $doc->setText('Lorem ipsum...');
        $this->doc = $doc;
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

    /**
     * find translation in non-default language and then save it back has to keep language
     */
    public function testFindTranslationAndUpdate()
    {
        $this->dm->persist($this->doc);
        $this->dm->bindTranslation($this->doc, 'en');
        $this->dm->flush();

        $this->doc->topic = 'Un sujet intéressant';
        $this->dm->bindTranslation($this->doc, 'fr');
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->findTranslation(null, '/functional/' . $this->testNodeName, 'fr');
        $doc->topic = 'Un sujet intéressant';
        $this->dm->flush();

        $this->assertDocumentStored();
    }

    /**
     * changing the locale and flushing should pick up changes automatically
     */
    public function testUpdateLocalAndFlush()
    {
        $this->dm->persist($this->doc);
        $this->dm->bindTranslation($this->doc, 'en');
        $this->dm->flush();

        $this->doc->topic = 'Un sujet intéressant';
        $this->doc->locale = 'fr';
        $this->dm->flush();

        $this->assertDocumentStored();
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

        $doc = $this->dm->find('Doctrine\Tests\Models\Translation\Article', '/functional/' . $this->testNodeName);

        $this->assertNotNull($doc);
        $this->assertEquals('en', $doc->locale);

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

        $doc = $this->dm->findTranslation('Doctrine\Tests\Models\Translation\Article', '/functional/' . $this->testNodeName, 'fr');

        $this->assertNotNull($doc);
        $this->assertEquals('fr', $doc->locale);
        $this->assertEquals('Un autre sujet', $doc->topic);
    }

    /**
     * Italian translation does not exist so as defined in $localePrefs we
     * will get french as it has higher priority than english
     */
    public function testFindTranslationWithLanguageFallback()
    {
        $this->dm->persist($this->doc);
        $this->doc->topic = 'Un autre sujet';
        $this->doc->locale = 'fr';
        $this->dm->flush();

        $doc = $this->dm->findTranslation('Doctrine\Tests\Models\Translation\Article', '/functional/' . $this->testNodeName, 'it');

        $this->assertNotNull($doc);
        $this->assertEquals('fr', $doc->locale);
        $this->assertEquals('Un autre sujet', $doc->topic);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindTranslationWithInvalidLanguageFallback()
    {
        $this->dm->persist($this->doc);
        $this->dm->flush();

        $this->dm->findTranslation('Doctrine\Tests\Models\Translation\Article', '/functional/' . $this->testNodeName, 'es');
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

        $doc = $this->dm->find('Doctrine\Tests\Models\Translation\Article', '/functional/' . $this->testNodeName);
        $this->assertNull($doc, 'Document must be null after deletion');

        $doc = new Article();
        $doc->id = '/functional/' . $this->testNodeName;
        $doc->author = 'John Doe';
        $doc->topic = 'Some interesting subject';
        $doc->setText('Lorem ipsum...');
        $this->dm->persist($doc);
        $this->dm->bindTranslation($doc, 'en');
        $this->dm->flush();

        $locales = $this->dm->getLocalesFor($doc);
        $this->assertEquals(array('en'), $locales, 'Removing a document must remove all translations');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidTranslationStrategy()
    {
        $doc = new InvalidMapping();
        $doc->id = '/functional/' . $this->testNodeName;
        $doc->topic = 'foo';
        $this->dm->persist($doc);
        $this->dm->bindTranslation($doc, 'en');
        $this->dm->flush();
    }

    /**
     * bindTranslation with a document that is not persisted should fail
     *
     * @expectedException \InvalidArgumentException
     */
    public function testBindTranslationWithoutPersist()
    {
        $doc = new CmsArticle();
        $doc->id = '/functional/' . $this->testNodeName;
        $this->dm->bindTranslation($doc, 'en');
    }

    /**
     * bindTranslation with a document that is not translatable should fail
     *
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testBindTranslationNonTranslatable()
    {
        $doc = new CmsArticle();
        $doc->id = '/functional/' . $this->testNodeName;
        $this->dm->persist($doc);
        $this->dm->bindTranslation($doc, 'en');
    }
}
