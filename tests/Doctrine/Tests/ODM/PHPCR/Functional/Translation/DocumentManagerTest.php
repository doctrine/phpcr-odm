<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Translation;

use Doctrine\Tests\Models\Translation\Article;
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
        $this->dm->setTranslationStrategy(new AttributeTranslationStrategy());
        $this->dm->setLocaleChooserStrategy(new LocaleChooser($localePrefs, 'en'));

        $this->session = $this->dm->getPhpcrSession();
        $this->metadata = $this->dm->getClassMetadata('Doctrine\Tests\Models\Translation\Article');

        $doc = new Article();
        $doc->id = '/' . $this->testNodeName;
        $doc->author = 'John Doe';
        $doc->topic = 'Some interesting subject';
        $doc->text = 'Lorem ipsum...';
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

        $this->assertTrue($node->hasProperty('lang-en-topic'));
        $this->assertEquals('Some interesting subject', $node->getPropertyValue('lang-en-topic'));

        $this->assertTrue($node->hasProperty('author'));
        $this->assertEquals('John Doe', $node->getPropertyValue('author'));

        $this->assertEquals('en', $this->doc->locale);
    }

    public function testPersistTranslation()
    {
        $this->removeTestNode();

        $this->dm->persist($this->doc);

        $this->doc->topic = 'Un sujet intéressant';

        $this->dm->persistTranslation($this->doc, 'fr');
        $this->dm->flush();

        $node = $this->getTestNode();
        $this->assertNotNull($node);

        $this->assertFalse($node->hasProperty('topic'));

        $this->assertTrue($node->hasProperty('lang-en-topic'));
        $this->assertEquals('Some interesting subject', $node->getPropertyValue('lang-en-topic'));
        $this->assertTrue($node->hasProperty('lang-fr-topic'));
        $this->assertEquals('Un sujet intéressant', $node->getPropertyValue('lang-fr-topic'));

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

        $this->assertTrue($node->hasProperty('lang-fr-topic'));
        $this->assertEquals('Un autre sujet', $node->getPropertyValue('lang-fr-topic'));

        $this->assertEquals('fr', $this->doc->locale);
    }

    public function testFind()
    {
        $doc = $this->dm->find('Doctrine\Tests\Models\Translation\Article', '/' . $this->testNodeName);

        $this->assertNotNull($doc);
        $this->assertEquals('en', $this->doc->locale);

        $node = $this->getTestNode();
        $this->assertNotNull($node);
        $this->assertTrue($node->hasProperty('lang-en-topic'));
        $this->assertEquals('Some interesting subject', $node->getPropertyValue('lang-en-topic'));
    }

    public function testFindTranslation()
    {
        $doc = $this->dm->findTranslation('Doctrine\Tests\Models\Translation\Article', '/' . $this->testNodeName, 'fr');

        $this->assertNotNull($doc);
        $this->assertEquals('fr', $doc->locale);
        $this->assertEquals('Un autre sujet', $doc->topic);
    }

//    public function testFindTranslationWithLanguageFallback()
//    {
//        $doc = $this->dm->findTranslation('Doctrine\Tests\Models\Translation\Article', '/' . $this->testNodeName, 'it');
//
//        // Italian translation does not exist so as defined in $localePrefs we will get english
//
//        $this->assertNotNull($doc);
//        $this->assertEquals('fr', $doc->locale);
//        $this->assertEquals('Un autre sujet', $doc->topic);
//    }

//    public function testFindTranslationWithInvalidLanguageFallback()
//    {
//        $doc = $this->dm->findTranslation('Doctrine\Tests\Models\Translation\Article', '/' . $this->testNodeName, 'es');
//
//        $this->assertNotNull($doc);
//        $this->assertEquals('fr', $doc->locale);
//        $this->assertEquals('Un autre sujet', $doc->topic);
//    }

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
