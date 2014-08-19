<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Translation;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Translation\Translation;
use Doctrine\ODM\PHPCR\Translation\TranslationStrategy\AttributeTranslationStrategy;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;

use Doctrine\Tests\Models\Translation\Article;

class AttributeTranslationStrategyTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    protected $testNodeName = '__test-node__';

    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * @var \PHPCR\SessionInterface
     */
    private $session;

    /**
     * @var \PHPCR\WorkspaceInterface
     */
    private $workspace;

    /**
     * @var ClassMetadata
     */
    private $metadata;

    public function setup()
    {
        $this->dm = $this->createDocumentManager();
        $this->session = $this->dm->getPhpcrSession();
        $this->workspace = $this->dm->getPhpcrSession()->getWorkspace();
        $this->metadata = $this->dm->getClassMetadata('Doctrine\Tests\Models\Translation\Article');
    }

    public function tearDown()
    {
        try {
            $this->removeTestNode();
        } catch (\Exception $ignore) {
            // do nothing
        }
    }

    public function testSaveTranslation()
    {
        // First save some translations
        $data = array();
        $data['topic'] = 'Some interesting subject';
        $data['text'] ='Lorem ipsum...';

        $node = $this->getTestNode();

        $strategy = new AttributeTranslationStrategy($this->dm);
        $strategy->saveTranslation($data, $node, $this->metadata, 'en');

        // Save translation in another language

        $data['topic'] = 'Un sujet intéressant';

        $strategy->saveTranslation($data, $node, $this->metadata, 'fr');
        $this->dm->flush();

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

        $strategy = new AttributeTranslationStrategy($this->dm);
        $this->assertTrue($strategy->loadTranslation($doc, $node, $this->metadata, 'en'));

        // And check the translatable properties have the correct value
        $this->assertEquals('English topic', $doc->topic);
        $this->assertEquals('English text', $doc->getText());
        $this->assertEquals(array(), $doc->getSettings()); // nullable

        // Load another language and test the document has been updated
        $strategy->loadTranslation($doc, $node, $this->metadata, 'fr');

        $this->assertEquals('Sujet français', $doc->topic);
        $this->assertEquals('Texte français', $doc->getText());
        $this->assertEquals(array(), $doc->getSettings());
    }

    public function testLoadTranslationNotNullable()
    {
        // Create the node in the content repository
        $node = $this->getTestNode();
        $node->setProperty('author', 'John Doe');

        $this->session->save();

        // Then try to read it's translation
        $doc = new Article();

        $strategy = new AttributeTranslationStrategy($this->dm);
        $this->assertFalse($strategy->loadTranslation($doc, $node, $this->metadata, 'en'));
    }

    /**
     * Test what happens if some document field is null.
     *
     * If either load or save fail fix that first, as this test uses both.
     */
    public function testTranslationNullProperties()
    {
        // First save some translations
        $data = array();
        $data['topic'] = 'Some interesting subject';
        $data['text'] = 'Lorem ipsum...';
        $data['nullable'] = 'not null';
        $data['settings'] = array('key' => 'value');

        $node = $this->getTestNode();
        $node->setProperty('author', 'John Doe');

        $strategy = new AttributeTranslationStrategy($this->dm);
        $strategy->saveTranslation($data, $node, $this->metadata, 'en');

        // Save translation in another language
        $data = array();
        $data['topic'] = 'Un sujet intéressant';
        $data['text'] = 'Lorem français';

        $strategy->saveTranslation($data, $node, $this->metadata, 'fr');
        $this->dm->flush();

        $doc = new Article;
        $doc->author = 'John Doe';
        $doc->topic = $data['topic'];
        $doc->setText($data['text']);
        $strategy->loadTranslation($doc, $node, $this->metadata, 'en');
        $this->assertEquals('Some interesting subject', $doc->topic);
        $this->assertEquals('Lorem ipsum...', $doc->getText());
        $this->assertEquals('not null', $doc->nullable);
        $this->assertEquals(array('key' => 'value'), $doc->getSettings());

        $strategy->loadTranslation($doc, $node, $this->metadata, 'fr');
        $this->assertEquals('Un sujet intéressant', $doc->topic);
        $this->assertEquals('Lorem français', $doc->getText());
        $this->assertNull($doc->nullable);
        $this->assertEquals(array(), $doc->getSettings());
    }

    public function testRemoveTranslation()
    {
        // First save some translations
        $data = array();
        $data['topic'] = 'Some interesting subject';
        $data['text'] = 'Lorem ipsum...';
        $data['settings'] = array('setting-1' => 'one-setting');

        $node = $this->getTestNode();
        $node->setProperty('author', 'John Doe');

        $strategy = new AttributeTranslationStrategy($this->dm);
        $strategy->saveTranslation($data, $node, $this->metadata, 'en');
        $data['topic'] = 'sujet interessant';
        $data['text'] = null;
        $strategy->saveTranslation($data, $node, $this->metadata, 'fr');

        $this->assertTrue($node->hasProperty(self::propertyNameForLocale('en', 'topic')));
        $this->assertTrue($node->hasProperty(self::propertyNameForLocale('en', 'text')));
        $this->assertTrue($node->hasProperty('phpcr_locale:frnullfields'));

        // Then remove the french translation
        $doc = new Article;
        $doc->author = 'John Doe';
        $doc->topic = $data['topic'];
        $doc->setText($data['text']);
        $strategy->removeTranslation($doc, $node, $this->metadata, 'fr');

        $this->assertFalse($node->hasProperty(self::propertyNameForLocale('fr', 'topic')));
        $this->assertFalse($node->hasProperty(self::propertyNameForLocale('fr', 'text')));
        $this->assertFalse($node->hasProperty('phpcr_locale:frnullfields'));
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

        $strategy = new AttributeTranslationStrategy($this->dm);
        $locales = $strategy->getLocalesFor($doc, $node, $this->metadata);

        $this->assertTrue(is_array($locales));
        $this->assertCount(3, $locales);
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
        if (!$this->session) {
            return;
        }
        $root = $this->session->getRootNode();
        if ($root->hasNode($this->testNodeName)) {
            $root->getNode($this->testNodeName)->remove();
            $this->session->save();
        }
    }

    public static function propertyNameForLocale($locale, $property)
    {
        return Translation::LOCALE_NAMESPACE . ':' . $locale . '-' . $property;
    }

    /**
     * Caution : Jackalope\Property guess the property type on the first element
     * So if it's an boolean, all your array will be set to true
     * The Array has to be an array of string
     */
    public function testTranslationArrayProperties()
    {
        // First save some translations
        $data = array();
        $data['topic'] = 'Some interesting subject';
        $data['text'] = 'Lorem ipsum...';
        $data['settings'] = array(
            'is-active' => 'true',
            'url'       => 'great-article-in-english.html'
        );

        $node = $this->getTestNode();
        $node->setProperty('author', 'John Doe');

        $strategy = new AttributeTranslationStrategy($this->dm);
        $strategy->saveTranslation($data, $node, $this->metadata, 'en');

        // Save translation in another language

        $data['topic'] = 'Un sujet intéressant';
        $data['settings'] = array(
            'is-active' => 'true',
            'url'       => 'super-article-en-francais.html'
        );

        $strategy->saveTranslation($data, $node, $this->metadata, 'fr');
        $this->dm->flush();

        $doc = new Article;
        $doc->author = 'John Doe';
        $doc->topic = $data['topic'];
        $doc->setText($data['text']);
        $strategy->loadTranslation($doc, $node, $this->metadata, 'en');

        $this->assertEquals(array('is-active', 'url'), array_keys($doc->getSettings()));
        $this->assertEquals(array(
            'is-active' => 'true',
            'url'       => 'great-article-in-english.html'
        ), $doc->getSettings());

        $strategy->loadTranslation($doc, $node, $this->metadata, 'fr');
        $this->assertEquals(array('is-active', 'url'), array_keys($doc->getSettings()));
        $this->assertEquals(array(
            'is-active' => 'true',
            'url'       => 'super-article-en-francais.html'
        ), $doc->getSettings());
    }

    public function testQueryBuilder()
    {
        $strategy = new AttributeTranslationStrategy($this->dm);
        $this->dm->setTranslationStrategy('attribute', $strategy);
        $this->dm->setLocaleChooserStrategy(new LocaleChooser(array('en' => array('fr'), 'fr' => array('en')), 'en'));

        // First save some translations
        $data = array();
        $data['topic'] = 'Some interesting subject';
        $data['text'] = 'Lorem ipsum...';
        $data['settings'] = array(
            'is-active' => 'true',
            'url'       => 'great-article-in-english.html'
        );

        $node = $this->getTestNode();
        $node->setProperty('author', 'John Doe');
        $node->setProperty('phpcr:class', 'Doctrine\Tests\Models\Translation\Article');

        $strategy->saveTranslation($data, $node, $this->metadata, 'en');

        // Save translation in another language

        $data['topic'] = 'Un sujet intéressant';
        $data['settings'] = array(
            'is-active' => 'true',
            'url'       => 'super-article-en-francais.html'
        );

        $strategy->saveTranslation($data, $node, $this->metadata, 'fr');
        $this->dm->getPhpcrSession()->save();

        $qb = $this->dm->createQueryBuilder();
        $qb->from()->document('Doctrine\Tests\Models\Translation\Article', 'a');
        $qb->where()
            ->eq()
            ->field('a.topic')
            ->literal('Not Exist')
            ->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(0, $res);

        $qb = $this->dm->createQueryBuilder();
        $qb->from()->document('Doctrine\Tests\Models\Translation\Article', 'a');
        $qb->where()
            ->eq()
            ->field('a.topic')
            ->literal('Un sujet intéressant')
            ->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(0, $res);

        $qb = $this->dm->createQueryBuilder();
        $qb->setLocale(false);
        $qb->from()->document('Doctrine\Tests\Models\Translation\Article', 'a');
        $qb->where()
            ->eq()
            ->field('a.topic')
            ->literal('Un sujet intéressant')
            ->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(0, $res);

        $qb = $this->dm->createQueryBuilder();
        $qb->from()->document('Doctrine\Tests\Models\Translation\Article', 'a');
        $qb->where()
            ->eq()
            ->field('a.topic')
            ->literal('Some interesting subject')
            ->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(1, $res);

        $qb = $this->dm->createQueryBuilder();
        $qb->setLocale('fr');
        $qb->from()->document('Doctrine\Tests\Models\Translation\Article', 'a');
        $qb->where()
            ->eq()
            ->field('a.topic')
            ->literal('Un sujet intéressant')
            ->end();

        $res = $qb->getQuery()->execute();
        $this->assertCount(1, $res);
    }
}
