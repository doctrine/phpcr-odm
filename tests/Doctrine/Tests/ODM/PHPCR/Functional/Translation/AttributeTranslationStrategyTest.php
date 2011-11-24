<?php

namespace Doctrine\Tests\ODM\PHPCR\Translation;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory,
    Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy;

use Doctrine\Tests\Models\Translation\Article;

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
        //$this->removeTestNode();
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

        $this->assertTrue($node->hasProperty('lang-en-topic'));
        $this->assertTrue($node->hasProperty('lang-fr-topic'));
        $this->assertTrue($node->hasProperty('lang-en-text'));
        $this->assertTrue($node->hasProperty('lang-fr-text'));
        $this->assertFalse($node->hasProperty('lang-fr-author'));
        $this->assertFalse($node->hasProperty('lang-en-author'));

        $this->assertEquals('Some interesting subject', $node->getPropertyValue('lang-en-topic'));
        $this->assertEquals('Un sujet intéressant', $node->getPropertyValue('lang-fr-topic'));
        $this->assertEquals('Lorem ipsum...', $node->getPropertyValue('lang-en-text'));
        $this->assertEquals('Lorem ipsum...', $node->getPropertyValue('lang-fr-text'));
    }

    public function testLoadTranslation()
    {
        // Create the node in the content repository
        $node = $this->getTestNode();
        $node->setProperty('lang-en-topic', 'English topic');
        $node->setProperty('lang-en-text', 'English text');
        $node->setProperty('lang-fr-topic', 'Sujet français');
        $node->setProperty('lang-fr-text', 'Texte français');
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
        $this->dm->flush();

        $this->assertTrue($node->hasProperty('lang-en-topic'));
        $this->assertTrue($node->hasProperty('lang-en-text'));

        // Then remove the translations
        $strategy->removeTranslation($doc, $node, $this->metadata, 'en');
        $this->dm->flush();

        $this->assertNull($doc->topic);
        $this->assertNull($doc->text);
        $this->assertNotNull($doc->author);

        $this->assertFalse($node->hasProperty('lang-en-topic'));
        $this->assertFalse($node->hasProperty('lang-en-text'));
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

}
