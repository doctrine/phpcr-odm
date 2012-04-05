<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;

abstract class PropertyMappingTest extends \PHPUnit_Framework_TestCase
{
    abstract protected function loadDriver();
    /**
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver::loadMetadataForClass
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver::loadMetadataForClass
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver::loadMetadataForClass
     */
    public function testLoadMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\PropertyMappingObj';
        $mappingDriver = $this->loadDriver();

        $class = new ClassMetadata($className);
        $class->initializeReflection(new RuntimeReflectionService());
        $mappingDriver->loadMetadataForClass($className, $class);

        return $class;
    }

    /**
     * @depends testLoadMapping
     * @param ClassMetadata $class
     */
    public function testPropertyMappings($class)
    {
        $this->assertCount(10, $class->fieldMappings);
        $this->assertTrue(isset($class->fieldMappings['string']));
        $this->assertEquals('string', $class->fieldMappings['string']['name']);
        $this->assertTrue(isset($class->fieldMappings['binary']));
        $this->assertEquals('binary', $class->fieldMappings['binary']['name']);
        $this->assertTrue(isset($class->fieldMappings['long']));
        $this->assertEquals('long', $class->fieldMappings['long']['name']);
        $this->assertTrue(isset($class->fieldMappings['decimal']));
        $this->assertEquals('decimal', $class->fieldMappings['decimal']['name']);
        $this->assertTrue(isset($class->fieldMappings['double']));
        $this->assertEquals('double', $class->fieldMappings['double']['name']);
        $this->assertTrue(isset($class->fieldMappings['date']));
        $this->assertEquals('date', $class->fieldMappings['date']['name']);
        $this->assertTrue(isset($class->fieldMappings['boolean']));
        $this->assertEquals('boolean', $class->fieldMappings['boolean']['name']);
        $this->assertTrue(isset($class->fieldMappings['name']));
        $this->assertEquals('name', $class->fieldMappings['name']['name']);
        $this->assertTrue(isset($class->fieldMappings['path']));
        $this->assertEquals('path', $class->fieldMappings['path']['name']);
        $this->assertTrue(isset($class->fieldMappings['uri']));
        $this->assertEquals('uri', $class->fieldMappings['uri']['name']);
        return $class;
    }

    /**
     * @depends testLoadMapping
     * @param ClassMetadata $class
     */
    public function testNodenameMapping($class)
    {
        $this->assertTrue(isset($class->nodename));
        $this->assertEquals('namefield', $class->nodename);
    }

    /**
     * @depends testLoadMapping
     * @param ClassMetadata $class
     */
    public function testParentDocumentMapping($class)
    {
        $this->assertTrue(isset($class->parentMapping));
        $this->assertEquals('parent', $class->parentMapping);
    }
}


class PropertyMappingObj
{
    public $id;
    public $namefield;
    public $parent;
    public $string;
    public $binary;
    public $long;
    public $decimal;
    public $double;
    public $date;
    public $boolean;
    public $name;
    public $path;
    public $uri;
}
