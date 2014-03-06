<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\PHPCR\DocumentRepository as BaseDocumentRepository;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

class ClassMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTypeOfField()
    {
        $cmi = new ClassMetadata('Doctrine\Tests\ODM\PHPCR\Mapping\Person');
        $cmi->initializeReflection(new RuntimeReflectionService());
        $this->assertEquals(null, $cmi->getTypeOfField('some_field'));
        $cmi->mappings['some_field'] = array('type' => 'some_type');
        $this->assertEquals('some_type', $cmi->getTypeOfField('some_field'));
    }

    public function testClassName()
    {
        $cm = new ClassMetadata('Doctrine\Tests\ODM\PHPCR\Mapping\Person');
        $cm->initializeReflection(new RuntimeReflectionService());
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\Person', $cm->name);
        $this->assertInstanceOf('ReflectionClass', $cm->reflClass);

        return $cm;
    }

    /**
     * @depends testClassName
     */
    public function testIsValidNodename(ClassMetadata $cm)
    {
        $this->assertInstanceOf('PHPCR\RepositoryException', $cm->isValidNodename(''));
        $this->assertInstanceOf('PHPCR\RepositoryException', $cm->isValidNodename('a:b:c'));
        $this->assertInstanceOf('PHPCR\RepositoryException', $cm->isValidNodename('a:'));
        $this->assertInstanceOf('PHPCR\RepositoryException', $cm->isValidNodename(':a'));
        $this->assertInstanceOf('PHPCR\RepositoryException', $cm->isValidNodename(':'));
        $this->assertInstanceOf('PHPCR\RepositoryException', $cm->isValidNodename('x/y'));

        $this->assertNull($cm->isValidNodename('a:b'));
        $this->assertNull($cm->isValidNodename('b'));
    }

    /**
     * @depends testClassName
     */
    public function testMapFieldWithId(ClassMetadata $cm)
    {
        $cm->mapField(array('fieldName' => 'id', 'id' => true, 'strategy' => 'assigned'));

        $this->assertTrue(isset($cm->mappings['id']));
        $this->assertEquals(
            array(
                'id' => true,
                'property' => 'id',
                'type' => 'string',
                'fieldName' => 'id',
                'multivalue' => false,
                'strategy' => 'assigned',
                'nullable' => false,
            )
            , $cm->mappings['id']
        );

        $this->assertEquals('id', $cm->identifier);
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_ASSIGNED, $cm->idGenerator);

        return $cm;
    }

    /**
     * @depends testClassName
     */
    public function testHasFieldNull(ClassMetadata $cm)
    {
        $this->assertFalse($cm->hasField(null));
    }

    /**
     * @depends testClassName
     *
     * @expectedException \Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testGetAssociationNonexisting(ClassMetadata $cm)
    {
        $cm->getAssociation('nonexisting');
    }

    /**
     * @depends testMapFieldWithId
     *
     * @expectedException \Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testGetFieldNonexisting(ClassMetadata $cm)
    {
        $cm->getField('nonexisting');
    }

    /**
     * @depends testMapFieldWithId
     */
    public function testMapField(ClassMetadata $cm)
    {
        $cm->mapField(array('fieldName' => 'username', 'property' => 'username', 'type' => 'string'));
        $cm->mapField(array('fieldName' => 'created', 'property' => 'created', 'type' => 'datetime'));

        $this->assertTrue(isset($cm->mappings['username']));
        $this->assertTrue(isset($cm->mappings['created']));

        $this->assertEquals(
            array(
                'property' => 'username',
                'type' => 'string',
                'fieldName' => 'username',
                'multivalue' => false,
                'nullable' => false,
            ),
            $cm->mappings['username']
        );
        $this->assertEquals(
            array(
                'property' => 'created',
                'type' => 'datetime',
                'fieldName' => 'created',
                'multivalue' => false,
                'nullable' => false,
            ),
            $cm->mappings['created']
        );

        return $cm;
    }

    /**
     * Mapping should return translated fields.
     * @depends testMapFieldWithId
     */
    public function testMapFieldWithInheritance(ClassMetadata $cmp) {
        // Load parent document metadata.
        $ar = new AnnotationReader();
        $ad = new AnnotationDriver($ar);
        $ad->loadMetadataForClass($cmp->getName(), $cmp);

        // Initialize subclass metadata.
        $cm = new ClassMetadata('Doctrine\Tests\ODM\PHPCR\Mapping\Customer');
        $cm->initializeReflection(new RuntimeReflectionService());

        // Test that the translated field is being inherited.
        $mapping = array(
            'property' => 'translatedField',
            'fieldName' => 'translatedField',
            'translated' => true
        );
        $cm->mapField($mapping, $cmp);
        $this->assertEquals(array('translatedField'), $cm->translatableFields);
    }

    /**
     * @depends testMapField
     * @expectedException \Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testMapFieldWithoutNameThrowsException(ClassMetadata $cm)
    {
        $cm->mapField(array());
    }

    /**
     * @depends testMapField
     * @expectedException \Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testMapNonExistingField(ClassMetadata $cm)
    {
        $cm->mapField(array('fieldName' => 'notexisting'));
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testMapChildInvalidName()
    {
        $cm = new ClassMetadata('Doctrine\Tests\ODM\PHPCR\Mapping\Address');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapChild(array('fieldName' => 'child', 'nodeName' => 'in/valid'));
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Mapping\MappingException
     */
    public function testMapChildrenInvalidFetchDepth()
    {
        $cm = new ClassMetadata('Doctrine\Tests\ODM\PHPCR\Mapping\Person');
        $cm->initializeReflection(new RuntimeReflectionService());
        $cm->mapChildren(array('fieldName' => 'address', 'fetchDepth' => 'invalid'));
    }

    /**
     * @depends testMapField
     */
    public function testReflectionProperties(ClassMetadata $cm)
    {
        $this->assertInstanceOf('ReflectionProperty', $cm->reflFields['username']);
        $this->assertInstanceOf('ReflectionProperty', $cm->reflFields['created']);
    }

    /**
     * @depends testMapField
     */
    public function testNewInstance(ClassMetadata $cm)
    {
        $instance1 = $cm->newInstance();
        $instance2 = $cm->newInstance();

        $this->assertInstanceOf('Doctrine\Tests\ODM\PHPCR\Mapping\Person', $instance1);
        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * @depends testClassName
     */
    public function testSerialize(ClassMetadata $cm)
    {
        $expected = 'O:40:"Doctrine\ODM\PHPCR\Mapping\ClassMetadata":18:{s:8:"nodeType";s:15:"nt:unstructured";s:10:"identifier";s:2:"id";s:4:"name";s:39:"Doctrine\Tests\ODM\PHPCR\Mapping\Person";s:11:"idGenerator";i:2;s:8:"mappings";a:5:{s:2:"id";a:7:{s:9:"fieldName";s:2:"id";s:2:"id";b:1;s:8:"strategy";s:8:"assigned";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:8:"nullable";b:0;s:8:"property";s:2:"id";}s:8:"username";a:5:{s:9:"fieldName";s:8:"username";s:8:"property";s:8:"username";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:8:"nullable";b:0;}s:7:"created";a:5:{s:9:"fieldName";s:7:"created";s:8:"property";s:7:"created";s:4:"type";s:8:"datetime";s:10:"multivalue";b:0;s:8:"nullable";b:0;}s:6:"locale";a:3:{s:9:"fieldName";s:6:"locale";s:4:"type";s:6:"locale";s:8:"property";s:6:"locale";}s:15:"translatedField";a:7:{s:9:"fieldName";s:15:"translatedField";s:4:"type";s:6:"string";s:10:"translated";b:1;s:8:"property";s:15:"translatedField";s:10:"multivalue";b:0;s:5:"assoc";N;s:8:"nullable";b:0;}}s:13:"fieldMappings";a:4:{i:0;s:2:"id";i:1;s:8:"username";i:2;s:7:"created";i:3;s:15:"translatedField";}s:17:"referenceMappings";a:0:{}s:17:"referrersMappings";a:0:{}s:22:"mixedReferrersMappings";a:0:{}s:16:"childrenMappings";a:0:{}s:13:"childMappings";a:0:{}s:25:"customRepositoryClassName";s:51:"Doctrine\Tests\ODM\PHPCR\Mapping\DocumentRepository";s:18:"isMappedSuperclass";b:1;s:11:"versionable";b:1;s:18:"lifecycleCallbacks";a:1:{s:8:"postLoad";a:1:{i:0;s:8:"callback";}}s:13:"localeMapping";s:6:"locale";s:10:"translator";s:9:"attribute";s:18:"translatableFields";a:1:{i:0;s:15:"translatedField";}}';

        $cm->setCustomRepositoryClassName('DocumentRepository');
        $cm->setVersioned(true);
        $cm->addLifecycleCallback('callback', 'postLoad');
        $cm->isMappedSuperclass = true;
        $this->assertEquals($expected, serialize($cm));
    }

    public function testUnserialize()
    {
        $cm = unserialize('O:40:"Doctrine\ODM\PHPCR\Mapping\ClassMetadata":15:{s:8:"nodeType";s:15:"nt:unstructured";s:10:"identifier";s:2:"id";s:4:"name";s:39:"Doctrine\Tests\ODM\PHPCR\Mapping\Person";s:11:"idGenerator";i:1;s:8:"mappings";a:5:{s:2:"id";a:7:{s:9:"fieldName";s:2:"id";s:2:"id";b:1;s:8:"strategy";s:10:"repository";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:8:"nullable";b:0;s:8:"property";s:2:"id";}s:8:"username";a:5:{s:9:"fieldName";s:8:"username";s:8:"property";s:8:"username";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:8:"nullable";b:0;}s:7:"created";a:5:{s:9:"fieldName";s:7:"created";s:8:"property";s:7:"created";s:4:"type";s:8:"datetime";s:10:"multivalue";b:0;s:8:"nullable";b:0;}s:6:"locale";a:3:{s:9:"fieldName";s:6:"locale";s:4:"type";s:6:"locale";s:8:"property";s:6:"locale";}s:15:"translatedField";a:7:{s:9:"fieldName";s:15:"translatedField";s:4:"type";s:6:"string";s:10:"translated";b:1;s:8:"property";s:15:"translatedField";s:10:"multivalue";b:0;s:5:"assoc";N;s:8:"nullable";b:0;}}s:13:"fieldMappings";a:4:{i:0;s:2:"id";i:1;s:8:"username";i:2;s:7:"created";i:3;s:15:"translatedField";}s:17:"referenceMappings";a:0:{}s:17:"referrersMappings";a:0:{}s:22:"mixedReferrersMappings";a:0:{}s:16:"childrenMappings";a:0:{}s:13:"childMappings";a:0:{}s:25:"customRepositoryClassName";s:51:"Doctrine\Tests\ODM\PHPCR\Mapping\DocumentRepository";s:18:"isMappedSuperclass";b:1;s:11:"versionable";b:1;s:18:"lifecycleCallbacks";a:1:{s:8:"postLoad";a:1:{i:0;s:8:"callback";}}}');

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Mapping\ClassMetadata', $cm);

        $this->assertEquals(array('callback'), $cm->getLifecycleCallbacks('postLoad'));
        $this->assertTrue($cm->isMappedSuperclass);
        $this->assertTrue($cm->versionable);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\DocumentRepository', $cm->customRepositoryClassName);
    }

    /**
     * @param ClassMetadata $cm
     * @depends testClassName
     */
    public function testMapAssociationManyToOne(ClassMetadata $cm)
    {
        $cm->mapManyToOne(array('fieldName' => 'address', 'targetDocument' => 'Doctrine\Tests\ODM\PHPCR\Mapping\Address'));

        $this->assertTrue(isset($cm->mappings['address']), "No 'address' in associations map.");
        $this->assertEquals(array(
            'fieldName' => 'address',
            'targetDocument' => 'Doctrine\Tests\ODM\PHPCR\Mapping\Address',
            'sourceDocument' => 'Doctrine\Tests\ODM\PHPCR\Mapping\Person',
            'type' => ClassMetadata::MANY_TO_ONE,
            'strategy' => 'weak',
            'cascade' => null,
            'property' => 'address',
        ), $cm->mappings['address']);

        return $cm;
    }

    public function testClassMetadataInstanceSerialization()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);

        // Test initial state
        $this->assertTrue(count($cm->getReflectionProperties()) == 0);
        $this->assertInstanceOf('ReflectionClass', $cm->reflClass);
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $cm->name);
        $this->assertEquals(array(), $cm->parentClasses);
        $this->assertEquals(0, count($cm->referenceMappings));

        // Customize state
        $cm->setParentClasses(array("UserParent"));
        $cm->setCustomRepositoryClassName("CmsUserRepository");
        $cm->setNodeType('foo:bar');
        $cm->mapManyToOne(array('fieldName' => 'address', 'targetDocument' => 'CmsAddress', 'mappedBy' => 'foo'));
        $this->assertEquals(1, count($cm->referenceMappings));

        $serialized = serialize($cm);
        /** @var ClassMetadata $cm */
        $cm = unserialize($serialized);
        $cm->wakeupReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);

        // Check state
        $this->assertTrue(count($cm->getReflectionProperties()) > 0);
        $this->assertEquals('Doctrine\Tests\Models\CMS', $cm->namespace);
        $this->assertInstanceOf('ReflectionClass', $cm->reflClass);
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $cm->name);
        $this->assertEquals(array('UserParent'), $cm->parentClasses);
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsUserRepository', $cm->customRepositoryClassName);
        $this->assertEquals('foo:bar', $cm->getNodeType());
        $this->assertEquals(ClassMetadata::MANY_TO_ONE, $cm->getTypeOfField('address'));
        $this->assertEquals(1, count($cm->referenceMappings));
        $this->assertTrue($cm->hasAssociation('address'));
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsAddress', $cm->getAssociationTargetClass('address'));
    }

    /**
     * @param ClassMetadata $cm
     * @depends testMapField
     */
    public function testClassMetadataInstanceSerializationTranslationProperties($cm)
    {
        $serialized = serialize($cm);
        /** @var ClassMetadata $cm */
        $cm = unserialize($serialized);
        $cm->wakeupReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);

        // Check properties needed for translations
        $this->assertEquals('attribute', $cm->translator);
        $this->assertTrue(in_array('translatedField', $cm->translatableFields));
        $this->assertEquals('locale', $cm->localeMapping);
    }
}

class Customer extends Person {}

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
     * @PHPCRODM\String(translated=true)
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
