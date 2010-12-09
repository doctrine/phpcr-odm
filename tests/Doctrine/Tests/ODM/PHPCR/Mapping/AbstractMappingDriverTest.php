<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

abstract class AbstractMappingDriverTest extends \PHPUnit_Framework_Testcase
{
    abstract protected function loadDriver();
    /**
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver::loadMetadataForClass
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver::loadMetadataForClass
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver::loadMetadataForClass
     */
    public function testLoadMapping()
    {
        $className = 'Doctrine\Tests\Models\CMS\CmsUser';
        $mappingDriver = $this->loadDriver();

        $class = new ClassMetadata($className);
        $mappingDriver->loadMetadataForClass($className, $class);

        return $class;
    }

    /**
     * @depends testLoadMapping
     * @param ClassMetadata $class
     */
    public function testFieldMappings($class)
    {
        $this->assertEquals(3, count($class->fieldMappings));
//        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['name']));
        $this->assertTrue(isset($class->fieldMappings['username']));
        $this->assertTrue(isset($class->fieldMappings['status']));

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testStringFieldMappings($class)
    {
        $this->assertEquals('string', $class->fieldMappings['name']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testIdentifier($class)
    {
        $this->assertEquals('id', $class->identifier);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testManyToOneAssociationMapping($class)
    {
        $this->assertArrayHasKey('rights', $class->associationsMappings);

        $this->assertEquals(array(
            'fieldName' => 'rights',
            'cascade' => 0,
            'targetDocument' => 'Doctrine\Tests\Models\CMS\CmsUserRights',
            'value' => null,
            'sourceDocument' => 'Doctrine\Tests\Models\CMS\CmsUser',
            'isOwning' => true,
            'type' => ClassMetadata::MANY_TO_ONE,
        ), $class->associationsMappings['rights']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testManyToManyAssociationMapping($class)
    {
        $this->assertArrayHasKey('groups', $class->associationsMappings);

        $this->assertEquals(array(
            'fieldName' => 'groups',
            'cascade' => 0,
            'mappedBy' => null,
            'targetDocument' => 'Doctrine\Tests\Models\CMS\CmsGroup',
            'value' => null,
            'sourceDocument' => 'Doctrine\Tests\Models\CMS\CmsUser',
            'isOwning' => true,
            'type' => ClassMetadata::MANY_TO_MANY,
        ), $class->associationsMappings['groups']);

        return $class;
    }
}
