<?php

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Id\AssignedIdGenerator;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;

class AssignedIdGeneratorTest extends TestCase
{
    /**
     * @covers Doctrine\ODM\PHPCR\Id\AssignedIdGenerator::generate
     */
    public function testGenerate()
    {
        $id = 'moo';

        $generator = new AssignedIdGenerator;
        $cm = new ClassMetadataProxy($id);
        $dm = $this->createMock(DocumentManager::class);

        $this->assertEquals($id, $generator->generate(null, $cm, $dm));
    }

    /**
     * @covers Doctrine\ODM\PHPCR\Id\AssignedIdGenerator::generate
     */
    public function testGenerateNoIdException()
    {
        $id = '';

        $generator = new AssignedIdGenerator;
        $cm = new ClassMetadataProxy($id);
        $dm = $this->createMock(DocumentManager::class);

        try {
            $generator->generate(null, $cm, $dm);
        } catch (\Exception $expected) {
            return;
        }
        $this->fail('Expected \Exception not thrown');
    }
}

class ClassMetadataProxy extends ClassMetadata
{
    protected $_value;

    public function __construct($value)
    {
        $this->_value = $value;
    }

    public function getFieldValue($document, $field)
    {
        return $this->_value;
    }
}
