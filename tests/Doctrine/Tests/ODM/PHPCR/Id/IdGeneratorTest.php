<?php

use Doctrine\ODM\PHPCR\Id\IdGenerator;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class IdGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateGeneratorTypeAssigned()
    {
        $generator = IdGenerator::create(ClassMetadata::GENERATOR_TYPE_ASSIGNED);
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Id\AssignedIdGenerator', $generator);
    }

    /**
     * @covers Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateGeneratorTypeRepository()
    {
        $generator = IdGenerator::create(ClassMetadata::GENERATOR_TYPE_REPOSITORY);
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Id\RepositoryIdGenerator', $generator);
    }

    /**
     * @covers Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateGeneratorTypeParent()
    {
        $generator = IdGenerator::create(ClassMetadata::GENERATOR_TYPE_PARENT);
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Id\ParentIdGenerator', $generator);
    }

    /**
     * @covers Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateGeneratorTypeAuto()
    {
        $generator = IdGenerator::create(ClassMetadata::GENERATOR_TYPE_AUTO);
        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Id\AutoIdGenerator', $generator);
    }

    /**
     * @expectedException \Doctrine\ODM\PHPCR\Exception\InvalidArgumentException
     * @covers Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateException()
    {
        IdGenerator::create(null);
    }
}

