<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Translation;

use Doctrine\Tests\Models\Translation\Article,
    Doctrine\Tests\Models\Translation\InvalidMapping,
    Doctrine\Tests\Models\CMS\CmsArticle;

use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy,
    Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;


class DocumentManagerTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    protected $testNodeName = '__my_test_node__';

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

        $this->session = $this->dm->getPhpcrSession();
        $this->metadata = $this->dm->getClassMetadata('Doctrine\Tests\Models\Translation\Article');

        $doc = new Article();
        $doc->id = '/' . $this->testNodeName;
        $doc->author = 'John Doe';
        $doc->topic = 'Some interesting subject';
        $doc->setText('Lorem ipsum...');
        $this->doc = $doc;
    }

    public function testPersistNew()
    {
        $this->removeTestNode();

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

    public function testPersistTranslation()
    {
        $this->removeTestNode();

        $this->dm->persistTranslation($this->doc, 'en');

        $this->doc->topic = 'Un sujet intéressant';

        $this->dm->persistTranslation($this->doc, 'fr');
        $this->dm->flush();

        $node = $this->getTestNode();
        $this->assertNotNull($node);

        $this->assertFalse($node->hasProperty('topic'));

        $this->assertTrue($node->hasProperty(AttributeTranslationStrategyTest::propertyNameForLocale('en', 'topic')));
        $this->assertEquals('Some interesting subject', $node->getPropertyValue(AttributeTranslationStrategyTest::propertyNameForLocale('en', 'topic')));
        $this->assertTrue($node->hasProperty(AttributeTranslationStrategyTest::propertyNameForLocale('fr', 'topic')));
        $this->assertEquals('Un sujet intéressant', $node->getPropertyValue(AttributeTranslationStrategyTest::propertyNameForLocale('fr', 'topic')));

        $this->assertEquals('fr', $this->doc->locale);
    }

    public function testFlush()
    {
        $this->removeTestNode();

        $this->dm->persist($this->doc);

        $this->doc->topic = 'Un sujet intéressant';

        $this->dm->persistTranslation($this->doc, 'fr');
        $this->dm->flush();

        $this->doc->topic = 'Un autre sujet';
        $this->dm->flush();

        $node = $this->getTestNode();
        $this->assertNotNull($node);

        $this->assertTrue($node->hasProperty(AttributeTranslationStrategyTest::propertyNameForLocale('fr', 'topic')));
        $this->assertEquals('Un autre sujet', $node->getPropertyValue(AttributeTranslationStrategyTest::propertyNameForLocale('fr', 'topic')));

        $this->assertEquals('fr', $this->doc->locale);
    }

    /**
     * @depends testPersistNew
     */
    public function testFind()
    {
        $doc = $this->dm->find('Doctrine\Tests\Models\Translation\Article', '/' . $this->testNodeName);

        $this->assertNotNull($doc);
        $this->assertEquals('en', $this->doc->locale);

        $node = $this->getTestNode();
        $this->assertNotNull($node);
        $this->assertTrue($node->hasProperty(AttributeTranslationStrategyTest::propertyNameForLocale('en', 'topic')));
        $this->assertEquals('Some interesting subject', $node->getPropertyValue(AttributeTranslationStrategyTest::propertyNameForLocale('en', 'topic')));
    }

    public function testFindTranslation()
    {
        $doc = $this->dm->findTranslation('Doctrine\Tests\Models\Translation\Article', '/' . $this->testNodeName, 'fr');

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
        $doc = $this->dm->findTranslation('Doctrine\Tests\Models\Translation\Article', '/' . $this->testNodeName, 'it');

        $this->assertNotNull($doc);
        $this->assertEquals('fr', $doc->locale);
        $this->assertEquals('Un autre sujet', $doc->topic);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindTranslationWithInvalidLanguageFallback()
    {
        $doc = $this->dm->findTranslation('Doctrine\Tests\Models\Translation\Article', '/' . $this->testNodeName, 'es');

        $this->assertNotNull($doc);
        $this->assertEquals('es', $doc->locale);
        $this->assertEquals('Un autre sujet', $doc->topic);
    }

    public function testGetLocaleFor()
    {
        // Only 1 language is persisted
        $this->removeTestNode();
        $this->dm->persist($this->doc);
        $this->dm->flush();

        $locales = $this->dm->getLocalesFor($this->doc);
        $this->assertEquals(1, count($locales));
        $this->assertTrue(in_array('en', $locales));

        // A second language is persisted
        $this->dm->persistTranslation($this->doc, 'fr');
        $this->dm->flush();

        $locales = $this->dm->getLocalesFor($this->doc);
        $this->assertEquals(2, count($locales));
        $this->assertTrue(in_array('en', $locales));
        $this->assertTrue(in_array('fr', $locales));

        // A third language is persisted
        $this->dm->persistTranslation($this->doc, 'de');
        $this->dm->flush();

        $locales = $this->dm->getLocalesFor($this->doc);
        $this->assertEquals(3, count($locales));
        $this->assertTrue(in_array('en', $locales));
        $this->assertTrue(in_array('fr', $locales));
        $this->assertTrue(in_array('de', $locales));
    }

    public function testRemove()
    {
        $this->removeTestNode();

        $this->dm->persistTranslation($this->doc, 'en');
        $this->dm->persistTranslation($this->doc, 'fr');
        $this->dm->flush();

        $this->dm->remove($this->doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find('Doctrine\Tests\Models\Translation\Article', '/' . $this->testNodeName);
        $this->assertNull($doc, 'Document must be null after deletion');

        $doc = new Article();
        $doc->id = '/' . $this->testNodeName;
        $doc->author = 'John Doe';
        $doc->topic = 'Some interesting subject';
        $doc->setText('Lorem ipsum...');
        $this->dm->persistTranslation($doc, 'en');

        $locales = $this->dm->getLocalesFor($doc);
        $this->assertEquals(array('en'), $locales, 'Removing a document must remove all translations');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidTranslationStrategy()
    {
        $this->removeTestNode();

        $doc = new InvalidMapping();
        $doc->id = '/' . $this->testNodeName;
        $this->dm->persistTranslation($doc, 'en');
        $this->dm->flush();
    }

    /**
     * persistTranslation with a document that is not translatable should fail
     *
     * @expectedException \Doctrine\ODM\PHPCR\PHPCRException
     */
    public function testPersistTranslationNonTranslatable()
    {
        $this->removeTestNode();
        $doc = new CmsArticle();
        $doc->id = '/' . $this->testNodeName;
        $this->dm->persistTranslation($doc, 'en');
    }

    protected function removeTestNode()
    {
        $root = $this->session->getRootNode();
        if ($root->hasNode($this->testNodeName)) {
            $root->getNode($this->testNodeName)->remove();
            $this->session->save();
        }
    }

    protected function getTestNode()
    {
        return $this->session->getNode('/' . $this->testNodeName);
    }
}
