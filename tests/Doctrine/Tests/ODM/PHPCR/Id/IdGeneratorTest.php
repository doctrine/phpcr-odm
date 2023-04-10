<?php

namespace Doctrine\Tests\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Id\AssignedIdGenerator;
use Doctrine\ODM\PHPCR\Id\AutoIdGenerator;
use Doctrine\ODM\PHPCR\Id\IdGenerator;
use Doctrine\ODM\PHPCR\Id\ParentIdGenerator;
use Doctrine\ODM\PHPCR\Id\RepositoryIdGenerator;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;

class IdGeneratorTest extends TestCase
{
    /**
     * @covers \Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateGeneratorTypeAssigned(): void
    {
        $generator = IdGenerator::create(ClassMetadata::GENERATOR_TYPE_ASSIGNED);
        $this->assertInstanceOf(AssignedIdGenerator::class, $generator);
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateGeneratorTypeRepository(): void
    {
        $generator = IdGenerator::create(ClassMetadata::GENERATOR_TYPE_REPOSITORY);
        $this->assertInstanceOf(RepositoryIdGenerator::class, $generator);
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateGeneratorTypeParent(): void
    {
        $generator = IdGenerator::create(ClassMetadata::GENERATOR_TYPE_PARENT);
        $this->assertInstanceOf(ParentIdGenerator::class, $generator);
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateGeneratorTypeAuto(): void
    {
        $generator = IdGenerator::create(ClassMetadata::GENERATOR_TYPE_AUTO);
        $this->assertInstanceOf(AutoIdGenerator::class, $generator);
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\Id\IdGenerator::create
     */
    public function testCreateException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IdGenerator::create(42);
    }
}
