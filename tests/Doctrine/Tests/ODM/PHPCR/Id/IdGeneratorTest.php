<?php

use Doctrine\ODM\PHPCR\Id\IdGenerator;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class IdGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Doctrine\ODM\PHPCR\Configuration
     */
    private $config;

    public function setUp()
    {
        $this->config = $this->getMock('Doctrine\ODM\PHPCR\Configuration');
    }

    /**
     * @covers Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateGeneratorTypeAssigned()
    {
        $generator = IdGenerator::create(ClassMetadata::GENERATOR_TYPE_ASSIGNED, $this->config);
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Id\AssignedIdGenerator', $generator);
    }

    /**
     * @covers Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateGeneratorTypeRepository()
    {
        $generator = IdGenerator::create(ClassMetadata::GENERATOR_TYPE_REPOSITORY, $this->config);
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Id\RepositoryIdGenerator', $generator);
    }

    /**
     * @covers Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateGeneratorTypeParent()
    {
        $generator = IdGenerator::create(ClassMetadata::GENERATOR_TYPE_PARENT, $this->config);
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Id\ParentIdGenerator', $generator);
    }

    /**
     * @covers Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateGeneratorTypeAuto()
    {
        $generator = IdGenerator::create(ClassMetadata::GENERATOR_TYPE_AUTO, $this->config);
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Id\AutoIdGenerator', $generator);
    }

    /**
     * @covers Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateGeneratorTypeFieldSlugifier()
    {
        $generator = IdGenerator::create(ClassMetadata::GENERATOR_TYPE_FIELD_SLUGIFIER, $this->config);
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Id\FieldSlugifierIdGenerator', $generator);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     * @covers Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateException()
    {
        IdGenerator::create('asd', $this->config);
    }
}

