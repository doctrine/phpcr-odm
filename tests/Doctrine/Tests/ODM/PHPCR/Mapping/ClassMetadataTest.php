<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;
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
    public function testMapFieldWithId(ClassMetadata $cm)
    {
        $cm->mapField(array('fieldName' => 'id', 'id' => true, 'strategy' => 'repository'));

        $this->assertTrue(isset($cm->mappings['id']));
        $this->assertEquals(
            array(
                'id' => true,
                'name' => 'id',
                'type' => 'string',
                'fieldName' => 'id',
                'multivalue' => false,
                'strategy' => 'repository',
            )
            , $cm->mappings['id']
        );

        $this->assertEquals('id', $cm->identifier);
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_REPOSITORY, $cm->idGenerator);

        return $cm;
    }

    /**
     * @depends testMapFieldWithId
     */
    public function testMapField(ClassMetadata $cm)
    {
        $cm->mapField(array('fieldName' => 'username', 'name' => 'username', 'type' => 'string'));
        $cm->mapField(array('fieldName' => 'created', 'name' => 'created', 'type' => 'datetime'));

        $this->assertTrue(isset($cm->mappings['username']));
        $this->assertTrue(isset($cm->mappings['created']));

        $this->assertEquals(
            array(
                'name' => 'username',
                'type' => 'string',
                'fieldName' => 'username',
                'multivalue' => false
            ),
            $cm->mappings['username']
        );
        $this->assertEquals(
            array(
                'name' => 'created',
                'type' => 'datetime',
                'fieldName' => 'created',
                'multivalue' => false
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
            'name' => 'translatedField',
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
        $expected = 'O:40:"Doctrine\ODM\PHPCR\Mapping\ClassMetadata":14:{s:10:"identifier";s:2:"id";s:4:"name";s:39:"Doctrine\Tests\ODM\PHPCR\Mapping\Person";s:11:"idGenerator";i:1;s:8:"mappings";a:5:{s:2:"id";a:6:{s:9:"fieldName";s:2:"id";s:2:"id";b:1;s:8:"strategy";s:10:"repository";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:4:"name";s:2:"id";}s:8:"username";a:4:{s:9:"fieldName";s:8:"username";s:4:"name";s:8:"username";s:4:"type";s:6:"string";s:10:"multivalue";b:0;}s:7:"created";a:4:{s:9:"fieldName";s:7:"created";s:4:"name";s:7:"created";s:4:"type";s:8:"datetime";s:10:"multivalue";b:0;}s:6:"locale";a:3:{s:9:"fieldName";s:6:"locale";s:4:"type";s:6:"locale";s:4:"name";s:6:"locale";}s:15:"translatedField";a:6:{s:9:"fieldName";s:15:"translatedField";s:4:"type";s:6:"string";s:10:"translated";b:1;s:4:"name";s:15:"translatedField";s:10:"multivalue";b:0;s:5:"assoc";N;}}s:13:"fieldMappings";a:4:{i:0;s:2:"id";i:1;s:8:"username";i:2;s:7:"created";i:3;s:15:"translatedField";}s:17:"referenceMappings";a:0:{}s:17:"referrersMappings";a:0:{}s:22:"mixedReferrersMappings";a:0:{}s:16:"childrenMappings";a:0:{}s:13:"childMappings";a:0:{}s:25:"customRepositoryClassName";s:25:"customRepositoryClassName";s:18:"isMappedSuperclass";b:1;s:11:"versionable";b:1;s:18:"lifecycleCallbacks";a:1:{s:5:"event";a:1:{i:0;s:8:"callback";}}}';
        $cm->setCustomRepositoryClassName('customRepositoryClassName');
        $cm->setVersioned(true);
        $cm->addLifecycleCallback('callback', 'event');
        $cm->isMappedSuperclass = true;

        $this->assertEquals($expected, serialize($cm));
    }

    public function testUnserialize()
    {
        $cm = unserialize('O:40:"Doctrine\ODM\PHPCR\Mapping\ClassMetadata":14:{s:10:"identifier";s:2:"id";s:4:"name";s:39:"Doctrine\Tests\ODM\PHPCR\Mapping\Person";s:11:"idGenerator";i:1;s:8:"mappings";a:5:{s:2:"id";a:6:{s:9:"fieldName";s:2:"id";s:2:"id";b:1;s:8:"strategy";s:10:"repository";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:4:"name";s:2:"id";}s:8:"username";a:4:{s:9:"fieldName";s:8:"username";s:4:"name";s:8:"username";s:4:"type";s:6:"string";s:10:"multivalue";b:0;}s:7:"created";a:4:{s:9:"fieldName";s:7:"created";s:4:"name";s:7:"created";s:4:"type";s:8:"datetime";s:10:"multivalue";b:0;}s:6:"locale";a:3:{s:9:"fieldName";s:6:"locale";s:4:"type";s:6:"locale";s:4:"name";s:6:"locale";}s:15:"translatedField";a:6:{s:9:"fieldName";s:15:"translatedField";s:4:"type";s:6:"string";s:10:"translated";b:1;s:4:"name";s:15:"translatedField";s:10:"multivalue";b:0;s:5:"assoc";N;}}s:13:"fieldMappings";a:4:{i:0;s:2:"id";i:1;s:8:"username";i:2;s:7:"created";i:3;s:15:"translatedField";}s:17:"referenceMappings";a:0:{}s:17:"referrersMappings";a:0:{}s:22:"mixedReferrersMappings";a:0:{}s:16:"childrenMappings";a:0:{}s:13:"childMappings";a:0:{}s:25:"customRepositoryClassName";s:25:"customRepositoryClassName";s:18:"isMappedSuperclass";b:1;s:11:"versionable";b:1;s:18:"lifecycleCallbacks";a:1:{s:5:"event";a:1:{i:0;s:8:"callback";}}}');

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Mapping\ClassMetadata', $cm);

        $this->assertEquals(array('callback'), $cm->getLifecycleCallbacks('event'));
        $this->assertTrue($cm->isMappedSuperclass);
        $this->assertTrue($cm->versionable);
        $this->assertEquals('customRepositoryClassName', $cm->customRepositoryClassName);
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
            'name' => 'address',
        ), $cm->mappings['address']);

        return $cm;
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
}
