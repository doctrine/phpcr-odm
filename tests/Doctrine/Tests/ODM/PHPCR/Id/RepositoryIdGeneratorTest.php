<?php

use Doctrine\ODM\PHPCR\Id\RepositoryIdGenerator;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class RepositoryIdGeneratorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Doctrine\ODM\PHPCR\Id\RepositoryIdGenerator::generate
     */
    public function testGenerate()
    {
        $id = 'moo';
        $cm = new RepositoryClassMetadataProxy($id);
        $repository = $this->getMock('Doctrine\ODM\PHPCR\Id\RepositoryIdInterface');
        $repository
            ->expects($this->once())
            ->method('generateId')
            ->with($this->equalTo(null))
            ->will($this->returnValue('generatedid'))
        ;
        $dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')->disableOriginalConstructor()->getMock();
        $dm
            ->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository))
        ;

        $generator = new RepositoryIdGenerator;

        $this->assertEquals('generatedid', $generator->generate(null, $cm,  $dm));
    }

    /**
     * @covers Doctrine\ODM\PHPCR\Id\RepositoryIdGenerator::generate
     */
    public function testGenerateNoIdException()
    {
        $id = 'moo';
        $generator = new RepositoryIdGenerator;
        $cm = new ClassMetadataProxy($id);
        $repository = $this->getMock('Doctrine\ODM\PHPCR\Id\RepositoryIdInterface');
        $repository
            ->expects($this->once())
            ->method('generateId')
            ->with($this->equalTo(null))
            ->will($this->throwException(new \Exception))
        ;
        $dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')->disableOriginalConstructor()->getMock();
        $dm
            ->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository))
        ;


        try {
            $generator->generate(null, $cm,  $dm);
        } catch (\Exception $expected) {
            return;
        }
        $this->fail('Expected \Exception not thrown');
    }
}

class RepositoryClassMetadataProxy extends ClassMetadata
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

