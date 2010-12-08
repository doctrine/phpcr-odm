<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class ClassMetadataTest extends \PHPUnit_Framework_Testcase
{
    public function testClassName()
    {
        $cm = new ClassMetadata("Doctrine\Tests\ODM\PHPCR\Mapping\Person");

        $this->assertEquals("Doctrine\Tests\ODM\PHPCR\Mapping\Person", $cm->name);
        $this->assertType('ReflectionClass', $cm->reflClass);

        return $cm;
    }

    /**
     * @depends testClassName
     */
    public function testMapPropertyWithId($cm)
    {
        $cm->mapProperty(array('fieldName' => 'id', 'id' => true));

        $this->assertTrue(isset($cm->fieldMappings['id']));
        $this->assertEquals(array('id' => true, 'name' => 'jcr:uuid', 'type' => 'string', 'fieldName' => 'id'), $cm->fieldMappings['id']);

        $this->assertEquals('id', $cm->identifier);

        return $cm;
    }

    /**
     * @depends testMapPropertyWithId
     */
    public function testMapProperty($cm)
    {
        $cm->mapProperty(array('fieldName' => 'username', 'name' => 'username', 'type' => 'string'));
        $cm->mapProperty(array('fieldName' => 'created', 'name' => 'created', 'type' => 'datetime'));

        $this->assertTrue(isset($cm->fieldMappings['username']));
        $this->assertTrue(isset($cm->fieldMappings['created']));

        $this->assertEquals(array('type' => 'string', 'name' => 'username', 'fieldName' => 'username'), $cm->fieldMappings['username']);
        $this->assertEquals(array('type' => 'datetime', 'name' => 'created', 'fieldName' => 'created'), $cm->fieldMappings['created']);

        return $cm;
    }

    /**
     * @depends testMapProperty
     */
    public function testMapFieldWithoutNameThrowsException($cm)
    {
        $this->setExpectedException('Doctrine\ODM\PHPCR\Mapping\MappingException');

        $cm->mapProperty(array());
    }

    /**
     * @depends testMapProperty
     */
    public function testReflectionProperties($cm)
    {
        $this->assertType('ReflectionProperty', $cm->reflFields['username']);
        $this->assertType('ReflectionProperty', $cm->reflFields['created']);
    }

    /**
     * @depends testMapProperty
     */
    public function testNewInstance($cm)
    {
        $instance1 = $cm->newInstance();
        $instance2 = $cm->newInstance();

        $this->assertType('Doctrine\Tests\ODM\PHPCR\Mapping\Person', $instance1);
        $this->assertNotSame($instance1, $instance2);
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
            'isOwning' => true,
            'type' => ClassMetadata::MANY_TO_ONE,
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