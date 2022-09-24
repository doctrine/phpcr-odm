<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\PHPCR\DocumentRepository as BaseDocumentRepository;
use Doctrine\ODM\PHPCR\Exception\OutOfBoundsException;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsUserRepository;
use PHPCR\RepositoryException;
use PHPUnit\Framework\TestCase;

class ClassMetadataTest extends TestCase
{
    public function testGetTypeOfField(): void
    {
        $cmi = new ClassMetadata(Person::class);
        $cmi->initializeReflection(new RuntimeReflectionService());
        $this->assertNull($cmi->getTypeOfField('some_field'));
        $cmi->mappings['some_field'] = ['type' => 'some_type'];
        $this->assertEquals('some_type', $cmi->getTypeOfField('some_field'));
    }

    public function testClassName(): ClassMetadata
    {
        $cm = new ClassMetadata(Person::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $this->assertEquals(Person::class, $cm->name);
        $this->assertInstanceOf('ReflectionClass', $cm->reflClass);

        return $cm;
    }

    /**
     * @depends testClassName
     */
    public function testIsValidNodename(ClassMetadata $cm): void
    {
        $this->assertInstanceOf(RepositoryException::class, $cm->isValidNodename(''));
        $this->assertInstanceOf(RepositoryException::class, $cm->isValidNodename('a:b:c'));
        $this->assertInstanceOf(RepositoryException::class, $cm->isValidNodename('a:'));
        $this->assertInstanceOf(RepositoryException::class, $cm->isValidNodename(':a'));
        $this->assertInstanceOf(RepositoryException::class, $cm->isValidNodename(':'));
        $this->assertInstanceOf(RepositoryException::class, $cm->isValidNodename('x/y'));

        $cm->isValidNodename('a:b');
        $cm->isValidNodename('b');
    }

    /**
     * @depends testClassName
     */
    public function testMapFieldWithId(ClassMetadata $cm): ClassMetadata
    {
        $cm->mapField(['fieldName' => 'id', 'id' => true, 'strategy' => 'assigned']);

        $this->assertArrayHasKey('id', $cm->mappings);
        $this->assertEquals(
            [
                'id' => true,
                'property' => 'id',
                'type' => 'string',
                'fieldName' => 'id',
                'multivalue' => false,
                'strategy' => 'assigned',
                'nullable' => false,
            ],
            $cm->mappings['id']
        );

        $this->assertEquals('id', $cm->identifier);
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_ASSIGNED, $cm->idGenerator);

        return $cm;
    }

    /**
     * @depends testClassName
     */
    public function testHasFieldNull(ClassMetadata $cm): void
    {
        $this->assertFalse($cm->hasField(null));
    }

    /**
     * @depends testClassName
     */
    public function testGetAssociationNonexisting(ClassMetadata $cm): void
    {
        $this->expectException(MappingException::class);
        $cm->getAssociation('nonexisting');
    }

    /**
     * @depends testMapFieldWithId
     */
    public function testGetFieldNonexisting(ClassMetadata $cm): void
    {
        $this->expectException(MappingException::class);
        $cm->getFieldMapping('nonexisting');
    }

    /**
     * @depends testMapFieldWithId
     */
    public function testMapField(ClassMetadata $cm): ClassMetadata
    {
        $cm->mapField(['fieldName' => 'username', 'property' => 'username', 'type' => 'string']);
        $cm->mapField(['fieldName' => 'created', 'property' => 'created', 'type' => 'datetime']);

        $this->assertArrayHasKey('username', $cm->mappings);
        $this->assertArrayHasKey('created', $cm->mappings);

        $this->assertEquals(
            [
                'property' => 'username',
                'type' => 'string',
                'fieldName' => 'username',
                'multivalue' => false,
                'nullable' => false,
            ],
            $cm->mappings['username']
        );
        $this->assertEquals(
            [
                'property' => 'created',
                'type' => 'datetime',
                'fieldName' => 'created',
                'multivalue' => false,
                'nullable' => false,
            ],
            $cm->mappings['created']
        );

        return $cm;
    }

    /**
     * Mapping should return translated fields.
     *
     * @depends testMapFieldWithId
     */
    public function testMapFieldWithInheritance(ClassMetadata $cmp): void
    {
        // Load parent document metadata.
        $ar = new AnnotationReader();
        $ad = new AnnotationDriver($ar);
        $ad->loadMetadataForClass($cmp->getName(), $cmp);

        // Initialize subclass metadata.
        $cm = new ClassMetadata(Customer::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        // Test that the translated field is being inherited.
        $mapping = [
            'property' => 'translatedField',
            'fieldName' => 'translatedField',
            'translated' => true,
        ];
        $cm->mapField($mapping, $cmp);
        $this->assertEquals(['translatedField'], $cm->translatableFields);
    }

    /**
     * @depends testMapField
     */
    public function testMapFieldWithoutNameThrowsException(ClassMetadata $cm): void
    {
        $this->expectException(MappingException::class);
        $cm->mapField([]);
    }

    /**
     * @depends testMapField
     */
    public function testMapNonExistingField(ClassMetadata $cm): void
    {
        $this->expectException(MappingException::class);
        $cm->mapField(['fieldName' => 'notexisting']);
    }

    public function testMapChildInvalidName(): void
    {
        $cm = new ClassMetadata(Address::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $cm->mapChild(['fieldName' => 'child', 'nodeName' => 'in/valid']);
    }

    public function testMapChildrenInvalidFetchDepth(): void
    {
        $cm = new ClassMetadata(Person::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $this->expectException(MappingException::class);
        $cm->mapChildren(['fieldName' => 'address', 'fetchDepth' => 'invalid']);
    }

    /**
     * @depends testMapField
     */
    public function testReflectionProperties(ClassMetadata $cm): void
    {
        $this->assertInstanceOf('ReflectionProperty', $cm->reflFields['username']);
        $this->assertInstanceOf('ReflectionProperty', $cm->reflFields['created']);
    }

    /**
     * @depends testMapField
     */
    public function testNewInstance(ClassMetadata $cm): void
    {
        $instance1 = $cm->newInstance();
        $instance2 = $cm->newInstance();

        $this->assertInstanceOf(Person::class, $instance1);
        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * @depends testClassName
     */
    public function testSerialize(ClassMetadata $cm): void
    {
        if (PHP_MAJOR_VERSION >= 8 && PHP_MINOR_VERSION >= 1) {
            // PHP 8.1 orders fields alphabetically. Semantically both are the same.
            $expected = 'O:40:"Doctrine\ODM\PHPCR\Mapping\ClassMetadata":20:{s:8:"nodeType";s:15:"nt:unstructured";s:10:"identifier";s:2:"id";s:4:"name";s:39:"Doctrine\Tests\ODM\PHPCR\Mapping\Person";s:11:"idGenerator";i:2;s:8:"mappings";a:5:{s:2:"id";a:7:{s:9:"fieldName";s:2:"id";s:2:"id";b:1;s:8:"strategy";s:8:"assigned";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:8:"nullable";b:0;s:8:"property";s:2:"id";}s:8:"username";a:5:{s:9:"fieldName";s:8:"username";s:8:"property";s:8:"username";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:8:"nullable";b:0;}s:7:"created";a:5:{s:9:"fieldName";s:7:"created";s:8:"property";s:7:"created";s:4:"type";s:8:"datetime";s:10:"multivalue";b:0;s:8:"nullable";b:0;}s:6:"locale";a:3:{s:9:"fieldName";s:6:"locale";s:4:"type";s:6:"locale";s:8:"property";s:6:"locale";}s:15:"translatedField";a:7:{s:9:"fieldName";s:15:"translatedField";s:8:"property";s:15:"translatedField";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:5:"assoc";N;s:8:"nullable";b:0;s:10:"translated";b:1;}}s:13:"fieldMappings";a:4:{i:0;s:2:"id";i:1;s:8:"username";i:2;s:7:"created";i:3;s:15:"translatedField";}s:17:"referenceMappings";a:0:{}s:17:"referrersMappings";a:0:{}s:22:"mixedReferrersMappings";a:0:{}s:16:"childrenMappings";a:0:{}s:13:"childMappings";a:0:{}s:25:"customRepositoryClassName";s:51:"Doctrine\Tests\ODM\PHPCR\Mapping\DocumentRepository";s:18:"isMappedSuperclass";b:1;s:11:"versionable";s:6:"simple";s:14:"uniqueNodeType";b:1;s:18:"lifecycleCallbacks";a:1:{s:8:"postLoad";a:1:{i:0;s:8:"callback";}}s:13:"inheritMixins";b:1;s:13:"localeMapping";s:6:"locale";s:10:"translator";s:9:"attribute";s:18:"translatableFields";a:1:{i:0;s:15:"translatedField";}}';
        } else {
            $expected = 'O:40:"Doctrine\ODM\PHPCR\Mapping\ClassMetadata":20:{s:8:"nodeType";s:15:"nt:unstructured";s:10:"identifier";s:2:"id";s:4:"name";s:39:"Doctrine\Tests\ODM\PHPCR\Mapping\Person";s:11:"idGenerator";i:2;s:8:"mappings";a:5:{s:2:"id";a:7:{s:9:"fieldName";s:2:"id";s:2:"id";b:1;s:8:"strategy";s:8:"assigned";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:8:"nullable";b:0;s:8:"property";s:2:"id";}s:8:"username";a:5:{s:9:"fieldName";s:8:"username";s:8:"property";s:8:"username";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:8:"nullable";b:0;}s:7:"created";a:5:{s:9:"fieldName";s:7:"created";s:8:"property";s:7:"created";s:4:"type";s:8:"datetime";s:10:"multivalue";b:0;s:8:"nullable";b:0;}s:6:"locale";a:3:{s:9:"fieldName";s:6:"locale";s:4:"type";s:6:"locale";s:8:"property";s:6:"locale";}s:15:"translatedField";a:7:{s:9:"fieldName";s:15:"translatedField";s:10:"translated";b:1;s:8:"property";s:15:"translatedField";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:5:"assoc";N;s:8:"nullable";b:0;}}s:13:"fieldMappings";a:4:{i:0;s:2:"id";i:1;s:8:"username";i:2;s:7:"created";i:3;s:15:"translatedField";}s:17:"referenceMappings";a:0:{}s:17:"referrersMappings";a:0:{}s:22:"mixedReferrersMappings";a:0:{}s:16:"childrenMappings";a:0:{}s:13:"childMappings";a:0:{}s:25:"customRepositoryClassName";s:51:"Doctrine\Tests\ODM\PHPCR\Mapping\DocumentRepository";s:18:"isMappedSuperclass";b:1;s:11:"versionable";s:6:"simple";s:14:"uniqueNodeType";b:1;s:18:"lifecycleCallbacks";a:1:{s:8:"postLoad";a:1:{i:0;s:8:"callback";}}s:13:"inheritMixins";b:1;s:13:"localeMapping";s:6:"locale";s:10:"translator";s:9:"attribute";s:18:"translatableFields";a:1:{i:0;s:15:"translatedField";}}';
        }

        $cm->setCustomRepositoryClassName('DocumentRepository');
        $cm->setVersioned('simple');
        $cm->setUniqueNodeType(true);
        $cm->addLifecycleCallback('callback', 'postLoad');
        $cm->isMappedSuperclass = true;
        $this->assertEquals($expected, serialize($cm));
    }

    public function testUnserialize(): void
    {
        $cm = unserialize('O:40:"Doctrine\ODM\PHPCR\Mapping\ClassMetadata":16:{s:8:"nodeType";s:15:"nt:unstructured";s:10:"identifier";s:2:"id";s:4:"name";s:39:"Doctrine\Tests\ODM\PHPCR\Mapping\Person";s:11:"idGenerator";i:1;s:8:"mappings";a:5:{s:2:"id";a:7:{s:9:"fieldName";s:2:"id";s:2:"id";b:1;s:8:"strategy";s:10:"repository";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:8:"nullable";b:0;s:8:"property";s:2:"id";}s:8:"username";a:5:{s:9:"fieldName";s:8:"username";s:8:"property";s:8:"username";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:8:"nullable";b:0;}s:7:"created";a:5:{s:9:"fieldName";s:7:"created";s:8:"property";s:7:"created";s:4:"type";s:8:"datetime";s:10:"multivalue";b:0;s:8:"nullable";b:0;}s:6:"locale";a:3:{s:9:"fieldName";s:6:"locale";s:4:"type";s:6:"locale";s:8:"property";s:6:"locale";}s:15:"translatedField";a:7:{s:9:"fieldName";s:15:"translatedField";s:4:"type";s:6:"string";s:10:"translated";b:1;s:8:"property";s:15:"translatedField";s:10:"multivalue";b:0;s:5:"assoc";N;s:8:"nullable";b:0;}}s:13:"fieldMappings";a:4:{i:0;s:2:"id";i:1;s:8:"username";i:2;s:7:"created";i:3;s:15:"translatedField";}s:17:"referenceMappings";a:0:{}s:17:"referrersMappings";a:0:{}s:22:"mixedReferrersMappings";a:0:{}s:16:"childrenMappings";a:0:{}s:13:"childMappings";a:0:{}s:25:"customRepositoryClassName";s:51:"Doctrine\Tests\ODM\PHPCR\Mapping\DocumentRepository";s:18:"isMappedSuperclass";b:1;s:11:"versionable";s:6:"simple";s:14:"uniqueNodeType";b:1;s:18:"lifecycleCallbacks";a:1:{s:8:"postLoad";a:1:{i:0;s:8:"callback";}}}');

        $this->assertInstanceOf(ClassMetadata::class, $cm);

        $this->assertEquals(['callback'], $cm->getLifecycleCallbacks('postLoad'));
        $this->assertTrue($cm->isMappedSuperclass);
        $this->assertSame('simple', $cm->versionable);
        $this->assertTrue($cm->uniqueNodeType);
        $this->assertTrue($cm->inheritMixins);
        $this->assertEquals(DocumentRepository::class, $cm->customRepositoryClassName);
    }

    /**
     * @depends testClassName
     */
    public function testMapAssociationManyToOne(ClassMetadata $cm): ClassMetadata
    {
        $cm->mapManyToOne(['fieldName' => 'address', 'targetDocument' => Address::class]);

        $this->assertArrayHasKey('address', $cm->mappings, "No 'address' in associations map.");
        $this->assertEquals([
            'fieldName' => 'address',
            'targetDocument' => Address::class,
            'sourceDocument' => Person::class,
            'type' => ClassMetadata::MANY_TO_ONE,
            'strategy' => 'weak',
            'cascade' => null,
            'property' => 'address',
        ], $cm->mappings['address']);

        return $cm;
    }

    public function testClassMetadataInstanceSerialization(): void
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        // Test initial state
        $this->assertCount(0, $cm->getReflectionProperties());
        $this->assertInstanceOf(\ReflectionClass::class, $cm->reflClass);
        $this->assertEquals(CmsUser::class, $cm->name);
        $this->assertEquals([], $cm->parentClasses);
        $this->assertCount(0, $cm->referenceMappings);

        // Customize state
        $cm->setParentClasses(['UserParent']);
        $cm->setCustomRepositoryClassName('CmsUserRepository');
        $cm->setNodeType('foo:bar');
        $cm->mapManyToOne(['fieldName' => 'address', 'targetDocument' => 'CmsAddress', 'mappedBy' => 'foo']);
        $this->assertCount(1, $cm->referenceMappings);

        $serialized = serialize($cm);
        /** @var ClassMetadata $cm */
        $cm = unserialize($serialized);
        $this->assertInstanceOf(ClassMetadata::class, $cm);
        $cm->wakeupReflection(new RuntimeReflectionService());

        // Check state
        $this->assertNotEmpty($cm->getReflectionProperties());
        $this->assertEquals('Doctrine\Tests\Models\CMS', $cm->namespace);
        $this->assertInstanceOf(\ReflectionClass::class, $cm->reflClass);
        $this->assertEquals(CmsUser::class, $cm->name);
        $this->assertEquals(['UserParent'], $cm->parentClasses);
        $this->assertEquals(CmsUserRepository::class, $cm->customRepositoryClassName);
        $this->assertEquals('foo:bar', $cm->getNodeType());
        $this->assertEquals(ClassMetadata::MANY_TO_ONE, $cm->getTypeOfField('address'));
        $this->assertCount(1, $cm->referenceMappings);
        $this->assertTrue($cm->hasAssociation('address'));
        $this->assertEquals(CmsAddress::class, $cm->getAssociationTargetClass('address'));
    }

    /**
     * @depends testMapField
     */
    public function testClassMetadataInstanceSerializationTranslationProperties(ClassMetadata $cm): void
    {
        $serialized = serialize($cm);
        /** @var ClassMetadata $cm */
        $cm = unserialize($serialized);
        $this->assertInstanceOf(ClassMetadata::class, $cm);
        $cm->wakeupReflection(new RuntimeReflectionService());

        // Check properties needed for translations
        $this->assertEquals('attribute', $cm->translator);
        $this->assertContains('translatedField', $cm->translatableFields);
        $this->assertEquals('locale', $cm->localeMapping);
    }

    /**
     * It should throw an exception if given a child class FQN when the
     * metadata is for a leaf.
     */
    public function testAssertValidChildClassesIsLeaf(): void
    {
        $cm = new ClassMetadata(Person::class);
        $childCm = new ClassMetadata('stdClass');
        $cm->setIsLeaf(true);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('has been mapped as a leaf');
        $cm->assertValidChildClass($childCm);
    }

    /**
     * It should return early if the mapped child classes value is an empty array (i.e. any child classes are permitted).
     *
     * @doesNotPerformAssertions
     */
    public function testAssertValidChildClassesEmpty(): void
    {
        $cm = new ClassMetadata(Person::class);
        $childCm = new ClassMetadata('stdClass');
        $cm->setChildClasses([]);
        $cm->assertValidChildClass($childCm);
    }

    /**
     * It should return early if the given class is an allowed child class.
     *
     * @doesNotPerformAssertions
     */
    public function testAssertValidChildClassesAllowed(): void
    {
        $cm = new ClassMetadata(Person::class);
        $cm->setChildClasses(['stdClass']);
        $childCm = new ClassMetadata('stdClass');
        $childCm->initializeReflection(new RuntimeReflectionService());
        $cm->assertValidChildClass($childCm);
    }

    /**
     * It should return early if the given class is an instance of an allowed class.
     */
    public function testAssertValidChildClassInstance(): void
    {
        $cm = new ClassMetadata(Person::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $childCm = new ClassMetadata(Customer::class);
        $childCm->initializeReflection(new RuntimeReflectionService());
        $cm->setChildClasses([Person::class]);
        $result = $cm->assertValidChildClass($childCm);
        $this->assertNull($result);
    }

    /**
     * It should return early if the given class implements an allowed interface.
     */
    public function testAssertValidChildClassInterface(): void
    {
        $cm = new ClassMetadata(Person::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $childCm = new ClassMetadata('ArrayAccess');
        $childCm->initializeReflection(new RuntimeReflectionService());
        $cm->setChildClasses(['ArrayAccess']);
        $result = $cm->assertValidChildClass($childCm);
        $this->assertNull($result);
    }

    /**
     * It should throw an exception if the given class is not allowed.
     */
    public function testAssertValidChildClassesNotAllowed(): void
    {
        $cm = new ClassMetadata(Person::class);
        $cm->initializeReflection(new RuntimeReflectionService());
        $childCm = new ClassMetadata('stdClass');
        $childCm->initializeReflection(new RuntimeReflectionService());
        $cm->setChildClasses([Person::class]);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('does not allow children of type "stdClass"');
        $cm->assertValidChildClass($childCm);
    }
}

class Customer extends Person
{
}

/**
 * @PHPCRODM\Document(translator="attribute")
 */
class Person
{
    public $id;

    public $username;

    public $created;

    public $address;

    public $version;

    public $attachments;

    /**
     * @PHPCRODM\Locale
     */
    public $locale;

    /**
     * @PHPCRODM\Field(type="string", translated=true)
     */
    public $translatedField;
}

class Address
{
    public $id;

    public $child;
}

class DocumentRepository extends BaseDocumentRepository
{
}
