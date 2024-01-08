<?php

namespace Doctrine\Tests\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Id\AssignedIdGenerator;
use Doctrine\ODM\PHPCR\Id\IdException;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;

class AssignedIdGeneratorTest extends TestCase
{
    /**
     * @covers \Doctrine\ODM\PHPCR\Id\AssignedIdGenerator::generate
     */
    public function testGenerate(): void
    {
        $id = 'moo';

        $generator = new AssignedIdGenerator();
        $cm = new ClassMetadataProxy($id);
        $cm->identifier = 'id';
        $dm = $this->createMock(DocumentManager::class);

        $this->assertEquals($id, $generator->generate($this, $cm, $dm));
    }

    /**
     * @covers \Doctrine\ODM\PHPCR\Id\AssignedIdGenerator::generate
     */
    public function testGenerateNoIdException(): void
    {
        $id = '';

        $generator = new AssignedIdGenerator();
        $cm = new ClassMetadataProxy($id);
        $dm = $this->createMock(DocumentManager::class);

        $this->expectException(IdException::class);
        $generator->generate($this, $cm, $dm);
    }
}

class ClassMetadataProxy extends ClassMetadata
{
    protected $_value;

    public function __construct($value)
    {
        $this->_value = $value;
    }

    public function getFieldValue(object $document, string $field): mixed
    {
        return $this->_value;
    }
}
