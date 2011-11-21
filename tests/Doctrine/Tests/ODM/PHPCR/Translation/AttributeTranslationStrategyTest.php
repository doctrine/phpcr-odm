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
    }

    public function tearDown()
    {
        //$this->removeTestNode();
    }

    public function testSaveTranslations()
    {
        // First save some translations
        $doc = new Article();
        $doc->author = 'John Doe';
        $doc->topic = 'Some interesting subject';
        $doc->text = 'Lorem ipsum...';

        $meta = $this->dm->getClassMetadata('Doctrine\Tests\Models\Translation\Article');

        $node = $this->getTestNode();

        $strategy = new AttributeTranslationStrategy();
        $strategy->saveTranslations($doc, $node, $meta, 'en');

        $doc->topic = 'Un sujet intéressant';

        $strategy->saveTranslations($doc, $node, $meta, 'fr');
        $this->dm->flush();

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

    public function testLoadTranslations()
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
        $meta = $this->dm->getClassMetadata('Doctrine\Tests\Models\Translation\Article');
        $strategy = new AttributeTranslationStrategy();
        $strategy->loadTranslations($doc, $node, $meta, 'en');

        // And check the translatable properties have the correct value
        $this->assertEquals('English topic', $doc->topic);
        $this->assertEquals('English text', $doc->text);

        // Load another language and test the document has been updated
        $strategy->loadTranslations($doc, $node, $meta, 'fr');

        $this->assertEquals('Sujet français', $doc->topic);
        $this->assertEquals('Texte français', $doc->text);
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
