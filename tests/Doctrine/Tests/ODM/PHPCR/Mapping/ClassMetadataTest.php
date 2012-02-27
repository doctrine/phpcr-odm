<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;

class ClassMetadataTest extends \PHPUnit_Framework_TestCase
{

    public function testGetTypeOfField()
    {
        $cmi = new ClassMetadata('Doctrine\Tests\ODM\PHPCR\Mapping\Person');
        $cmi->initializeReflection(new RuntimeReflectionService());
        $this->assertEquals(null, $cmi->getTypeOfField('some_field'));
        $cmi->fieldMappings['some_field'] = array('type' => 'some_type');
        $this->assertEquals('some_type', $cmi->getTypeOfField('some_field'));
    }

    public function testClassName()
    {

        $cm = new ClassMetadata("Doctrine\Tests\ODM\PHPCR\Mapping\Person");
        $cm->initializeReflection(new RuntimeReflectionService());
        $this->assertEquals("Doctrine\Tests\ODM\PHPCR\Mapping\Person", $cm->name);
        $this->assertInstanceOf('ReflectionClass', $cm->reflClass);

        return $cm;
    }

    /**
     * @depends testClassName
     */
    public function testMapFieldWithId($cm)
    {
        $cm->mapField(array('fieldName' => 'id', 'id' => true, 'strategy' => 'repository'));

        $this->assertTrue(isset($cm->fieldMappings['id']));
        $this->assertEquals(
            array(
                'id' => true,
                'name' => 'id',
                'type' => 'string',
                'fieldName' => 'id',
                'multivalue' => false,
                'strategy' => 'repository',
            )
            , $cm->fieldMappings['id']
        );

        $this->assertEquals('id', $cm->identifier);
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_REPOSITORY, $cm->idGenerator);

        return $cm;
    }

    /**
     * @depends testMapFieldWithId
     */
    public function testMapField($cm)
    {
        $cm->mapField(array('fieldName' => 'username', 'name' => 'username', 'type' => 'string'));
        $cm->mapField(array('fieldName' => 'created', 'name' => 'created', 'type' => 'datetime'));

        $this->assertTrue(isset($cm->fieldMappings['username']));
        $this->assertTrue(isset($cm->fieldMappings['created']));

        $this->assertEquals(
            array(
                'name' => 'username',
                'type' => 'string',
                'fieldName' => 'username',
                'multivalue' => false
            ),
            $cm->fieldMappings['username']
        );
        $this->assertEquals(
            array(
                'name' => 'created',
                'type' => 'datetime',
                'fieldName' => 'created',
                'multivalue' => false
            ),
            $cm->fieldMappings['created']
        );

        return $cm;
    }

    /**
     * @depends testMapField
     */
    public function testMapFieldWithoutNameThrowsException($cm)
    {
        $this->setExpectedException('Doctrine\ODM\PHPCR\Mapping\MappingException');

        $cm->mapField(array());
    }

    /**
     * @depends testMapField
     */
    public function testReflectionProperties($cm)
    {
        $this->assertInstanceOf('ReflectionProperty', $cm->reflFields['username']);
        $this->assertInstanceOf('ReflectionProperty', $cm->reflFields['created']);
    }

    /**
     * @depends testMapField
     */
    public function testNewInstance($cm)
    {
        $instance1 = $cm->newInstance();
        $instance2 = $cm->newInstance();

        $this->assertInstanceOf('Doctrine\Tests\ODM\PHPCR\Mapping\Person', $instance1);
        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * @depends testClassName
     */
    public function testSerialize($cm)
    {
        $expected = 'O:40:"Doctrine\ODM\PHPCR\Mapping\ClassMetadata":9:{s:13:"fieldMappings";a:3:{s:2:"id";a:6:{s:9:"fieldName";s:2:"id";s:2:"id";b:1;s:8:"strategy";s:10:"repository";s:4:"type";s:6:"string";s:4:"name";s:2:"id";s:10:"multivalue";b:0;}s:8:"username";a:4:{s:9:"fieldName";s:8:"username";s:4:"name";s:8:"username";s:4:"type";s:6:"string";s:10:"multivalue";b:0;}s:7:"created";a:4:{s:9:"fieldName";s:7:"created";s:4:"name";s:7:"created";s:4:"type";s:8:"datetime";s:10:"multivalue";b:0;}}s:10:"identifier";s:2:"id";s:4:"name";s:39:"Doctrine\Tests\ODM\PHPCR\Mapping\Person";s:16:"generatorOptions";a:0:{}s:11:"idGenerator";i:1;s:25:"customRepositoryClassName";s:25:"customRepositoryClassName";s:18:"isMappedSuperclass";b:1;s:11:"versionable";b:1;s:18:"lifecycleCallbacks";a:1:{s:5:"event";a:1:{i:0;s:8:"callback";}}}';
        $cm->setCustomRepositoryClassName('customRepositoryClassName');
        $cm->setVersioned(true);
        $cm->addLifecycleCallback('callback', 'event');
        $cm->isMappedSuperclass = true;

        $this->assertEquals($expected, serialize($cm));
    }

    public function testUnserialize()
    {
        $cm = unserialize('O:40:"Doctrine\ODM\PHPCR\Mapping\ClassMetadata":11:{s:13:"fieldMappings";a:0:{}s:10:"identifier";N;s:4:"name";s:39:"Doctrine\Tests\ODM\PHPCR\Mapping\Person";s:9:"namespace";s:32:"Doctrine\Tests\ODM\PHPCR\Mapping";s:16:"generatorOptions";a:0:{}s:11:"idGenerator";i:2;s:25:"customRepositoryClassName";s:25:"customRepositoryClassName";s:18:"isMappedSuperclass";b:1;s:11:"versionable";b:1;s:12:"versionField";N;s:18:"lifecycleCallbacks";a:1:{s:5:"event";a:1:{i:0;s:8:"callback";}}}');

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
    public function testMapAssociationManyToOne($cm)
    {
        $cm->mapManyToOne(array('fieldName' => 'address', 'targetDocument' => 'Doctrine\Tests\ODM\PHPCR\Mapping\Address'));

        $this->assertTrue(isset($cm->associationsMappings['address']), "No 'address' in associations map.");
        $this->assertEquals(array(
            'fieldName' => 'address',
            'targetDocument' => 'Doctrine\Tests\ODM\PHPCR\Mapping\Address',
            'sourceDocument' => 'Doctrine\Tests\ODM\PHPCR\Mapping\Person',
            'type' => ClassMetadata::MANY_TO_ONE,
            'strategy' => 'weak',
        ), $cm->associationsMappings['address']);

        return $cm;
    }
}

class Person
{
    public $id;

    public $username;

    public $created;

    public $address;

    public $version;

    public $attachments;
}

class Address
{
    public $id;
}
