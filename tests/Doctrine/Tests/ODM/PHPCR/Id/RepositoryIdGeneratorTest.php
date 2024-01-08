<?php

namespace Doctrine\Tests\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Id\RepositoryIdGenerator;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;

class RepositoryIdGeneratorTest extends TestCase
{
    /**
     * @covers \Doctrine\ODM\PHPCR\Id\RepositoryIdGenerator::generate
     */
    public function testGenerate(): void
    {
        $id = 'moo';
        $cm = new RepositoryClassMetadataProxy($id);
        $repository = $this->createMock(ObjectRepositoryId::class);
        $repository
            ->expects($this->once())
            ->method('generateId')
            ->with($this)
            ->willReturn('generatedid');
        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $generator = new RepositoryIdGenerator();

        $this->assertEquals('generatedid', $generator->generate($this, $cm, $dm));
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\Id\RepositoryIdGenerator::generate
     */
    public function testGenerateNoIdException(): void
    {
        $id = 'moo';
        $generator = new RepositoryIdGenerator();
        $cm = new RepositoryClassMetadataProxy($id);
        $repository = $this->createMock(ObjectRepositoryId::class);
        $repository
            ->expects($this->once())
            ->method('generateId')
            ->with($this)
            ->willThrowException(new \Exception());
        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->expectException(\Exception::class);
        $generator->generate($this, $cm, $dm);
    }
}

class RepositoryClassMetadataProxy extends ClassMetadata
{
    protected $_value;

    public function __construct($value)
    {
        $this->_value = $value;
        $this->name = 'Test';
    }

    public function getFieldValue(object $document, string $field): mixed
    {
        return $this->_value;
    }
}
interface ObjectRepositoryId extends ObjectRepository, RepositoryIdInterface
{
}
