<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;

abstract class ChildMappingTest extends \PHPUnit_Framework_TestCase
{
    abstract protected function loadDriver();
    /**
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver::loadMetadataForClass
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver::loadMetadataForClass
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver::loadMetadataForClass
     */
    public function testLoadMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\ChildMappingObj';
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
    public function testChildMappings($class)
    {
        $this->assertCount(2, $class->childMappings);
        $this->assertTrue(isset($class->childMappings['child1']));
        $this->assertEquals('first', $class->childMappings['child1']['name']);
        $this->assertTrue(isset($class->childMappings['child2']));
        $this->assertEquals('second', $class->childMappings['child2']['name']);

        return $class;
    }

    /**
     * @depends testChildMappings
     * @param ClassMetadata $class
     */
    public function testChildrenMappings($class)
    {
        $this->assertCount(2, $class->childrenMappings);
        $this->assertTrue(isset($class->childrenMappings['all']));
        $this->assertFalse(isset($class->childrenMappings['all']['filter']));
        $this->assertTrue(isset($class->childrenMappings['some']));
        $this->assertEquals('*some*', $class->childrenMappings['some']['filter']);
    }

}


class ChildMappingObj
{
    public $id;
    public $name;
    public $child1;
    public $child2;
    public $all;
    public $some;
}

class ChildMappingChildObj
{
    public $id;
    public $name;
}
