<?php
/**
 * @author Uwe Jäger <uwej711@googlemail.com>
 */
namespace Doctrine\Tests\ODM\PHPCR\Functional\Translation;


use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @group functional
 */
class NotNullableTranslatedTest extends PHPCRFunctionalTestCase
{
    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->dm->setLocaleChooserStrategy(new LocaleChooser(array('en' => array('de'), 'de' => array('en')), 'en'));
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\MissingTranslationException
     */
    public function testExceptionNullableFieldNotSet()
    {
        $doc = new NotNullableTranslatedObject();
        $doc->id = $this->node->getPath().'/test';
        $this->dm->persist($doc);

        $doc->title = 'title';
        $this->dm->bindTranslation($doc, 'en');

        $doc->title = 'Titel';
        $this->dm->bindTranslation($doc, 'de');

        $this->dm->flush();

        $this->dm->clear();

        $this->dm->find(null, $this->node->getPath().'/test');
    }

}

/**
 * @PHPCRODM\Document(translator="attribute")
 */
class NotNullableTranslatedObject
{
    /**
     * @PHPCRODM\Id
     */
    public $id;

    /**
     * @PHPCRODM\Locale
     */
    public $locale;

    /**
     * @PHPCRODM\String(translated=true)
     */
    public $title;

    /**
     * @PHPCRODM\String(translated=true)
     */
    public $keywords;

}