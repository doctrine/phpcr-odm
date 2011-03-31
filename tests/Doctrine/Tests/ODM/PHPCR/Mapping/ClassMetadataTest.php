<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class ClassMetadataTest extends \PHPUnit_Framework_Testcase
{
    public function testClassName()
    {
        $cm = new ClassMetadata("Doctrine\Tests\ODM\PHPCR\Mapping\Person");

        $this->assertEquals("Doctrine\Tests\ODM\PHPCR\Mapping\Person", $cm->name);
        $this->assertInstanceOf('ReflectionClass', $cm->reflClass);

        return $cm;
    }

    /**
     * @depends testClassName
     */
    public function testMapFieldWithId($cm)
    {
        $cm->mapField(array('fieldName' => 'id', 'id' => true));

        $this->assertTrue(isset($cm->fieldMappings['id']));
        $this->assertEquals(
            array(
                'id' => true,
                'name' => 'id',
                'type' => 'string',
                'isInverseSide' => null,
                'isOwningSide' => true,
                'fieldName' => 'id',
                'multivalue' => false
            )
            , $cm->fieldMappings['id']
        );

        $this->assertEquals('id', $cm->identifier);

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
                'isInverseSide' => null,
                'isOwningSide' => true,
                'fieldName' => 'username',
                'multivalue' => false
            ),
            $cm->fieldMappings['username']
        );
        $this->assertEquals(
            array(
                'name' => 'created',
                'type' => 'datetime',
                'isInverseSide' => null,
                'isOwningSide' => true,
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
