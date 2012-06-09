<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;

abstract class AbstractMappingDriverTest extends \PHPUnit_Framework_TestCase
{
    abstract protected function loadDriver();
    abstract protected function loadDriverForTestMappingDocuments();
    
    protected function ensureIsLoaded($entityClassName)
    {
        new $entityClassName;
    }
    
    public function testLoadMetadataForNonDocumentThrowsException()
    {
        $cm = new ClassMetadata('stdClass');
        $cm->initializeReflection(new RuntimeReflectionService());
        
        $driver = $this->loadDriver();

        $this->setExpectedException('Doctrine\ODM\PHPCR\Mapping\MappingException');
        $driver->loadMetadataForClass('stdClass', $cm);
    }

    public function testGetAllClassNamesIsIdempotent()
    {
        $driver = $this->loadDriverForTestMappingDocuments();
        $original = $driver->getAllClassNames();

        $driver = $this->loadDriverForTestMappingDocuments();
        $afterTestReset = $driver->getAllClassNames();

        $this->assertEquals($original, $afterTestReset);
    }
    
    public function testGetAllClassNamesReturnsAlreadyLoadedClassesIfAppropriate()
    {
        $rightClassName = 'Doctrine\Tests\ODM\PHPCR\Mapping\Models\FieldMappingObject';
        $this->ensureIsLoaded($rightClassName);

        $driver = $this->loadDriverForTestMappingDocuments();
        $classes = $driver->getAllClassNames();

        $this->assertContains($rightClassName, $classes);
    }
    
    public function testGetAllClassNamesReturnsOnlyTheAppropriateClasses()
    {
        $extraneousClassName = 'Doctrine\Tests\Models\ECommerce\ECommerceCart';
        $this->ensureIsLoaded($extraneousClassName);

        $driver = $this->loadDriverForTestMappingDocuments();
        $classes = $driver->getAllClassNames();

        $this->assertNotContains($extraneousClassName, $classes);
    }
    
    /**
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver::loadMetadataForClass
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\YamlDriver::loadMetadataForClass
     * @covers Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver::loadMetadataForClass
     */
    public function testLoadMapping()
    {
        $className = 'Doctrine\Tests\ODM\PHPCR\Mapping\Models\FieldMappingObject';
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
    public function testFieldMappings($class)
    {
        $this->assertCount(12, $class->fieldMappings);
        $this->assertTrue(isset($class->fieldMappings['string']));
        $this->assertTrue(isset($class->fieldMappings['binary']));
        $this->assertTrue(isset($class->fieldMappings['long']));
        $this->assertTrue(isset($class->fieldMappings['int']));
        $this->assertTrue(isset($class->fieldMappings['decimal']));
        $this->assertTrue(isset($class->fieldMappings['double']));
        $this->assertTrue(isset($class->fieldMappings['float']));
        $this->assertTrue(isset($class->fieldMappings['date']));
        $this->assertTrue(isset($class->fieldMappings['boolean']));
        $this->assertTrue(isset($class->fieldMappings['name']));
        $this->assertTrue(isset($class->fieldMappings['path']));
        $this->assertTrue(isset($class->fieldMappings['uri']));

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
    public function testStringFieldMappings($class)
    {
        $this->assertEquals('string', $class->fieldMappings['string']['name']);
        $this->assertEquals('string', $class->fieldMappings['string']['type']);

        return $class;
    }
    
    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testBinaryFieldMappings($class)
    {
        $this->assertEquals('binary', $class->fieldMappings['binary']['name']);
        $this->assertEquals('binary', $class->fieldMappings['binary']['type']);

        return $class;
    }
    
    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testLongFieldMappings($class)
    {
        $this->assertEquals('long', $class->fieldMappings['long']['name']);
        $this->assertEquals('long', $class->fieldMappings['long']['type']);

        return $class;
    }
    
    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testIntFieldMappings($class)
    {
        $this->assertEquals('int', $class->fieldMappings['int']['name']);
        $this->assertEquals('long', $class->fieldMappings['int']['type']);

        return $class;
    }
    
    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testDecimalFieldMappings($class)
    {
        $this->assertEquals('decimal', $class->fieldMappings['decimal']['name']);
        $this->assertEquals('decimal', $class->fieldMappings['decimal']['type']);

        return $class;
    }
    
    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testDoubleFieldMappings($class)
    {
        $this->assertEquals('double', $class->fieldMappings['double']['name']);
        $this->assertEquals('double', $class->fieldMappings['double']['type']);

        return $class;
    }
    
    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testFloatFieldMappings($class)
    {
        $this->assertEquals('float', $class->fieldMappings['float']['name']);
        $this->assertEquals('double', $class->fieldMappings['float']['type']);

        return $class;
    }
    
    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testDateFieldMappings($class)
    {
        $this->assertEquals('date', $class->fieldMappings['date']['name']);
        $this->assertEquals('date', $class->fieldMappings['date']['type']);

        return $class;
    }
    
    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testBooleanFieldMappings($class)
    {
        $this->assertEquals('boolean', $class->fieldMappings['boolean']['name']);
        $this->assertEquals('boolean', $class->fieldMappings['boolean']['type']);

        return $class;
    }
    
    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testNameFieldMappings($class)
    {
        $this->assertEquals('name', $class->fieldMappings['name']['name']);
        $this->assertEquals('name', $class->fieldMappings['name']['type']);

        return $class;
    }
    
    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testPathFieldMappings($class)
    {
        $this->assertEquals('path', $class->fieldMappings['path']['name']);
        $this->assertEquals('path', $class->fieldMappings['path']['type']);

        return $class;
    }
    
    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testUriFieldMappings($class)
    {
        $this->assertEquals('uri', $class->fieldMappings['uri']['name']);
        $this->assertEquals('uri', $class->fieldMappings['uri']['type']);

        return $class;
    }
}
