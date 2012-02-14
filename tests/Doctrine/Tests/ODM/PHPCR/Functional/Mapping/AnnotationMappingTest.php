<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface,
    Doctrine\ODM\PHPCR\DocumentRepository,
    Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM,
    Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;

/**
 * @group functional
 */
class MappingTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    private $type;

    private $node;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);
    }

    public function testAnnotationInheritance()
    {
        $extending = new ExtendingClass();
        $extending->id = '/functional/extending';
        $this->dm->persist($extending);
    }
    public function testSecondLevelInheritance()
    {
        $second = new SecondLevel();
        $second->id = '/functional/second';
        $this->dm->persist($second);
    }

    public function testSecoundLevelOverwrite()
    {

        $localePrefs = array(
            'en' => array('en', 'de'),
            'de' => array('de', 'en'),
        );

        $this->dm->setLocaleChooserStrategy(new LocaleChooser($localePrefs, 'en'));

        $secondTrans = new SecondLevelOverwrite();
        $secondTrans->id = '/functional/secondTrans';
        $secondTrans->username = 'german';
        $this->dm->persist($secondTrans);
        $this->dm->bindTranslation($secondTrans, 'de');
        $secondTrans->username = 'english';
        $this->dm->bindTranslation($secondTrans, 'en');

        $this->dm->flush();

        $tmpDocDe = $this->dm->findTranslation(null, '/functional/secondTrans', 'de');

        $this->assertEquals($tmpDocDe->username, 'german');

        $tmpDocEn = $this->dm->findTranslation(null, '/functional/secondTrans', 'en');

        $this->assertEquals($tmpDocEn->username, 'english');
    }

    // TODO comprehensive test for all possible mapped fields in an abstract test, trying to persist and check if properly set
    // then dm->clear and check if still properly set.

    // then a test per mapping implementation extending the abstract test and providing documents with the mapping
}

/**
 * @PHPCRODM\Document()
 */
class Testclass
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\String */
    public $username;
    /** @PHPCRODM\Int(name="numbers", multivalue=true) */
    public $numbers;
}

/**
 * @PHPCRODM\Document()
 */
class ExtendingClass extends Testclass
{
    /** @PHPCRODM\String */
    public $username;
}

/**
 * @PHPCRODM\Document()
 */
class SecondLevel extends ExtendingClass
{
}

/**
 * @PHPCRODM\Document(translator="attribute")
 */
class SecondLevelOverwrite extends ExtendingClass
{
    /**
     * @PHPCRODM\Locale
     */
    public $locale;

    /** @PHPCRODM\String(translated=true) */
    public $username;
}
