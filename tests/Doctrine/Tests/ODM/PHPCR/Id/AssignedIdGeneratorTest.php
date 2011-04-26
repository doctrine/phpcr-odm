<?php

use Doctrine\ODM\PHPCR\Id\AssignedIdGenerator;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class AssignedIdGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Doctrine\ODM\PHPCR\Id\AssignedIdGenerator::generate
     */
    public function testGenerate()
    {
        $id = 'moo';

        $generator = new AssignedIdGenerator;
        $cm = new ClassMetadataProxy($id);
        $dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')->disableOriginalConstructor()->getMock();

        $this->assertEquals($id, $generator->generate(null, $cm,  $dm));
    }

    /**
     * @covers Doctrine\ODM\PHPCR\Id\AssignedIdGenerator::generate
     */
    public function testGenerateNoIdException()
    {
        $id = '';

        $generator = new AssignedIdGenerator;
        $cm = new ClassMetadataProxy($id);
        $dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')->disableOriginalConstructor()->getMock();

        try {
            $generator->generate(null, $cm,  $dm);
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

