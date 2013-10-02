<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Mapping;

use Doctrine\ODM\PHPCR\DocumentRepository,
    Doctrine\ODM\PHPCR\Mapping\ClassMetadata,
    Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM,
    Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;

/**
 * @group functional
 */
class AnnotationMappingTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    /**
     * @var \PHPCR\NodeInterface
     */
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
        $extending->text = 'test text';

        $this->dm->persist($extending);
        $this->dm->flush();

        $this->assertEquals(1, $extending->callback_run);
        $this->assertEquals(1, $extending->extending_run);
    }

    public function testSecondLevelInheritance()
    {
        $second = new SecondLevel();
        $second->id = '/functional/second';
        $this->dm->persist($second);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testSecondLevelInheritanceWithDuplicate()
    {
        $second = new SecondLevelWithDuplicate();
        $second->id = '/functional/second';
        $second->text = 'text test';
        $this->dm->persist($second);
    }

    public function testSecondLevelOverwrite()
    {
        $localePrefs = array(
            'en' => array('en', 'de'),
            'de' => array('de', 'en'),
        );

        $this->dm->setLocaleChooserStrategy(new LocaleChooser($localePrefs, 'en'));

        $secondTrans = new SecondLevelWithDuplicateOverwrite();
        $secondTrans->id = '/functional/secondTrans';
        $secondTrans->text = 'deutsch';
        $this->dm->persist($secondTrans);
        $this->dm->bindTranslation($secondTrans, 'de');
        $secondTrans->text = 'english';
        $this->dm->bindTranslation($secondTrans, 'en');

        $this->dm->flush();

        $tmpDocDe = $this->dm->findTranslation(null, '/functional/secondTrans', 'de');

        $this->assertEquals($tmpDocDe->text, 'deutsch');

        $tmpDocEn = $this->dm->findTranslation(null, '/functional/secondTrans', 'en');

        $this->assertEquals($tmpDocEn->text, 'english');
    }

    public function testIdStrategy()
    {
        $metadata = $this->dm->getClassMetadata('\Doctrine\Tests\ODM\PHPCR\Functional\Mapping\ParentIdStrategy');
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_PARENT, $metadata->idGenerator, 'parentId');
        $metadata = $this->dm->getClassMetadata('\Doctrine\Tests\ODM\PHPCR\Functional\Mapping\ParentIdStrategyDifferentOrder');
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_PARENT, $metadata->idGenerator, 'parentId2');
        $metadata = $this->dm->getClassMetadata('\Doctrine\Tests\ODM\PHPCR\Functional\Mapping\AutoNameIdStrategy');
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_AUTO, $metadata->idGenerator, 'autoname as only has parent but not nodename');
        $metadata = $this->dm->getClassMetadata('\Doctrine\Tests\ODM\PHPCR\Functional\Mapping\AssignedIdStrategy');
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_ASSIGNED, $metadata->idGenerator, 'assigned');
        $metadata = $this->dm->getClassMetadata('\Doctrine\Tests\ODM\PHPCR\Functional\Mapping\RepositoryIdStrategy');
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_REPOSITORY, $metadata->idGenerator, 'repository');
        $metadata = $this->dm->getClassMetadata('\Doctrine\Tests\ODM\PHPCR\Functional\Mapping\StandardCase');
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_ASSIGNED, $metadata->idGenerator, 'standardcase');
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
    public $text;
    public $callback_run = 0;

    /**
     * @PHPCRODM\PostPersist
     */
    public function callback()
    {
        $this->callback_run++;
    }
}

/**
 * @PHPCRODM\Document()
 */
class ExtendingClass extends Testclass
{
    /** @PHPCRODM\ReferenceOne */
    public $reference;

    public $extending_run = 0;

    /**
     * @PHPCRODM\PostPersist
     */
    public function extendingCallback()
    {
        $this->extending_run++;
    }
}

/**
 * @PHPCRODM\Document()
 */
class SecondLevel extends ExtendingClass
{
}

/**
 * @PHPCRODM\Document()
 */
class SecondLevelWithDuplicate extends ExtendingClass
{
    /** @PHPCRODM\Int */
    public $text;
}

/**
 * @PHPCRODM\Document(translator="attribute")
 */
class SecondLevelWithDuplicateOverwrite extends ExtendingClass
{
    /** @PHPCRODM\Locale */
    public $locale;
    /** @PHPCRODM\String(translated=true) */
    public $text;
}

/**
 * @PHPCRODM\Document
 */
class ParentIdStrategy
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Nodename */
    public $name;

    /** @PHPCRODM\ParentDocument */
    public $parent;
}

/**
 * @PHPCRODM\Document
 */
class ParentIdStrategyDifferentOrder
{
    /** @PHPCRODM\Nodename */
    public $name;

       /** @PHPCRODM\ParentDocument */
    public $parent;

    /** @PHPCRODM\Id */
    public $id;
}

/**
 * @PHPCRODM\Document
 */
class AutoNameIdStrategy
{
    /** @PHPCRODM\ParentDocument */
    public $parent;

    /** @PHPCRODM\Id() */
    public $id;
}

/**
 * @PHPCRODM\Document
 */
class AssignedIdStrategy
{
    /** @PHPCRODM\Id(strategy="assigned") */
    public $id;

    /** @PHPCRODM\Nodename */
    public $name;

    /** @PHPCRODM\ParentDocument */
    public $parent;
}

/**
 * @PHPCRODM\Document
 */
class RepositoryIdStrategy
{
    /** @PHPCRODM\Nodename */
    public $name;

    /** @PHPCRODM\ParentDocument */
    public $parent;

    /** @PHPCRODM\Id(strategy="repository") */
    public $id;
}

/**
 * @PHPCRODM\Document
 */
class StandardCase
{
    /** @PHPCRODM\Id */
    public $id;
}