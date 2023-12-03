<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Mapping;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;

/**
 * @group functional
 */
class AnnotationMappingTest extends PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    public function setUp(): void
    {
        $this->dm = $this->createDocumentManager([__DIR__], true);
        $this->resetFunctionalNode($this->dm);
    }

    public function testAnnotationInheritance(): void
    {
        $extending = new ExtendingClass();
        $extending->id = '/functional/extending';
        $extending->text = 'test text';

        $this->dm->persist($extending);
        $this->dm->flush();

        $this->assertEquals(1, $extending->callback_run);
        $this->assertEquals(1, $extending->extending_run);
    }

    public function testSecondLevelInheritance(): void
    {
        $second = new SecondLevel();
        $second->id = '/functional/second';
        $this->dm->persist($second);
    }

    public function testSecondLevelInheritanceWithDuplicate(): void
    {
        $second = new SecondLevelWithDuplicate();
        $second->id = '/functional/second';
        $second->text = 'text test';

        $this->expectException(MappingException::class);
        $this->dm->persist($second);
    }

    public function testSecondLevelOverwrite(): void
    {
        $localePrefs = [
            'en' => ['en', 'de'],
            'de' => ['de', 'en'],
        ];

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

    /**
     * @dataProvider generatorTypeProvider
     *
     * @param string $class       the fqn class name to load
     * @param int    $type        the generator type constant
     * @param string $description to be used in case of failure
     */
    public function testIdStrategy($class, $type, $description): void
    {
        $metadata = $this->dm->getClassMetadata($class);
        $this->assertEquals($type, $metadata->idGenerator, $description);
    }

    public function generatorTypeProvider(): array
    {
        return [
            [
                ParentIdStrategy::class,
                ClassMetadata::GENERATOR_TYPE_PARENT,
                'parentId',
            ],
            [
                ParentIdStrategyDifferentOrder::class,
                ClassMetadata::GENERATOR_TYPE_PARENT,
                'parentId2',
            ],
            [
                AutoNameIdStrategy::class,
                ClassMetadata::GENERATOR_TYPE_AUTO,
                'autoname as only has parent but not nodename',
            ],
            [
                AssignedIdStrategy::class,
                ClassMetadata::GENERATOR_TYPE_ASSIGNED,
                'assigned',
            ],
            [
                RepositoryIdStrategy::class,
                ClassMetadata::GENERATOR_TYPE_REPOSITORY,
                'repository',
            ],
            [
                StandardCase::class,
                ClassMetadata::GENERATOR_TYPE_ASSIGNED,
                'standardcase',
            ],
        ];
    }

    /**
     * @dataProvider invalidIdProvider
     *
     * @param string $class fqn of a class with invalid mapping
     */
    public function testInvalidId(string $class): void
    {
        $this->expectException(MappingException::class);
        $this->dm->getClassMetadata($class);
    }

    public function invalidIdProvider(): array
    {
        return [
            [
                ParentIdNoParentStrategy::class,
                AutoNameIdNoParentStrategy::class,
                NoId::class,
            ],
        ];
    }

    public function testPersistParentId(): void
    {
        $doc = new ParentIdStrategy();
        $doc->name = 'parent-strategy';
        $doc->parent = $this->dm->find(null, '/functional');
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();
        $this->assertInstanceOf(ParentIdStrategy::class, $this->dm->find(null, '/functional/parent-strategy'));
    }

    public function testPersistAutoNameId(): void
    {
        $doc = new AutoNameIdStrategy();
        $doc->parent = $this->dm->find(null, '/functional');
        $this->dm->persist($doc);
        $this->dm->flush();
        $id = $this->dm->getUnitOfWork()->getDocumentId($doc);
        $this->dm->clear();
        $this->assertInstanceOf(AutoNameIdStrategy::class, $this->dm->find(null, $id));
    }

    public function testPersistRepository(): void
    {
        $doc = new RepositoryIdStrategy();
        $doc->title = 'repository strategy';
        $this->dm->persist($doc);
        $this->dm->flush();
        $id = $this->dm->getUnitOfWork()->getDocumentId($doc);
        $this->dm->clear();
        $this->assertInstanceOf(RepositoryIdStrategy::class, $this->dm->find(null, $id));
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

    /** @PHPCRODM\Field(type="string") */
    public $text;

    /** @PHPCRODM\Depth */
    public $depth;

    public $callback_run = 0;

    /**
     * @PHPCRODM\PostPersist
     */
    public function callback(): void
    {
        ++$this->callback_run;
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
    public function extendingCallback(): void
    {
        ++$this->extending_run;
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
    /** @PHPCRODM\Field(type="long") */
    public $text;
}

/**
 * @PHPCRODM\Document(translator="attribute")
 */
class SecondLevelWithDuplicateOverwrite extends ExtendingClass
{
    /** @PHPCRODM\Locale */
    public $locale;

    /** @PHPCRODM\Field(type="string", translated=true) */
    public $text;
}

/**
 * @PHPCRODM\Document
 */
class ParentIdStrategy
{
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
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\ODM\PHPCR\Functional\Mapping\Repository")
 */
class RepositoryIdStrategy
{
    public $title;

    /** @PHPCRODM\Id(strategy="repository") */
    public $id;
}
class Repository extends DocumentRepository implements RepositoryIdInterface
{
    public function generateId($document, $parent = null)
    {
        return '/functional/'.str_replace(' ', '-', $document->title);
    }
}

/**
 * @PHPCRODM\Document
 *
 * Invalid document missing a parent mapping for the id strategy
 */
class ParentIdNoParentStrategy
{
    /** @PHPCRODM\Id(strategy="parent") */
    public $id;

    /** @PHPCRODM\Nodename */
    public $name;
}

/**
 * @PHPCRODM\Document
 *
 * Invalid document not having a parent mapping.
 */
class AutoNameIdNoParentStrategy
{
    /** @PHPCRODM\Id(strategy="auto") */
    public $id;
}

/**
 * @PHPCRODM\Document
 *
 * Invalid document not having an id at all.
 */
class NoId
{
}

/**
 * @PHPCRODM\Document
 */
class StandardCase
{
    /** @PHPCRODM\Id */
    public $id;
}
