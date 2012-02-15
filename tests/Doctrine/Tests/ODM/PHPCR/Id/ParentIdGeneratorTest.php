<?php

namespace Doctrine\Tests\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\Id\ParentIdGenerator;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class ParentIdGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Doctrine\ODM\PHPCR\Id\ParentIdGenerator::generate
     */
    public function testGenerate()
    {
        $id = 'moo';

        $generator = new ParentIdGenerator;
        $parent = new ParentDummy;
        $cm = new ParentClassMetadataProxy($parent, 'name', $id, new MockField($parent, '/miau'));
        $uow = $this->getMockBuilder('Doctrine\ODM\PHPCR\UnitOfWork')->disableOriginalConstructor()->getMock();
        $uow
            ->expects($this->once())
            ->method('getDocumentId')
            ->with($this->equalTo($parent))
            ->will($this->returnValue('/miau'))
        ;
        $dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')->disableOriginalConstructor()->getMock();
        $dm
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow))
        ;
        $this->assertEquals('/miau/name', $generator->generate(null, $cm,  $dm));
    }

    /**
     * @covers Doctrine\ODM\PHPCR\Id\ParentIdGenerator::generate
     */
    public function testGenerateNoParent()
    {
        $id = 'moo';

        $generator = new ParentIdGenerator;
        $cm = new ParentClassMetadataProxy(null, 'name', $id);
        $dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')->disableOriginalConstructor()->getMock();

        $this->assertEquals($id, $generator->generate(null, $cm,  $dm));
    }

    /**
     * @covers Doctrine\ODM\PHPCR\Id\ParentIdGenerator::generate
     */
    public function testGenerateNoName()
    {
        $id = 'moo';

        $generator = new ParentIdGenerator;
        $cm = new ParentClassMetadataProxy(new ParentDummy, '', $id);
        $dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')->disableOriginalConstructor()->getMock();

        $this->assertEquals($id, $generator->generate(null, $cm,  $dm));
    }

    public function testGenerateNoIdNoParent()
    {
        $id = '';

        $generator = new ParentIdGenerator;
        $cm = new ParentClassMetadataProxy(null, 'name', '');
        $dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')->disableOriginalConstructor()->getMock();

        try {
            $generator->generate(null, $cm,  $dm);
        } catch (\Exception $expected) {
            return;
        }
        $this->fail('Expected \Exception not thrown');
    }
    public function testGenerateNoIdNoName()
    {
        $id = '';

        $generator = new ParentIdGenerator;
        $cm = new ParentClassMetadataProxy(new ParentDummy, '', '');
        $dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')->disableOriginalConstructor()->getMock();

        try {
            $generator->generate(null, $cm,  $dm);
        } catch (\Exception $expected) {
            return;
        }
        $this->fail('Expected \Exception not thrown');
    }
}

class ParentDummy
{
}
class ParentClassMetadataProxy extends ClassMetadata
{
    public $parentMapping = 'parent';
    public $nodename = 'nodename';
    public $identifier = 'id';
    public $reflFields;

    protected $_parent, $_nodename, $_id;

    public function __construct($parentMapping, $nodename, $identifier, $mockField = null)
    {
        $this->_parent = $parentMapping;
        $this->_nodename = $nodename;
        $this->_identifier = $identifier;

        $this->reflFields = array($this->identifier => $mockField);
    }

    public function getFieldValue($document, $field)
    {
        switch($field) {
            case $this->parentMapping:
                return $this->_parent;
            case $this->nodename:
                return $this->_nodename;
            case $this->identifier:
                return $this->_identifier;
        }

        return null;
    }
}
class MockField
{
    private $p, $id;
    public function __construct($parent, $id)
    {
        $this->p = $parent;
        $this->id = $id;
    }
    public function getValue($parent)
    {
        if (! $this->p == $parent) throw new \Exception('Wrong parent passed in getValue');
        return $this->id;
    }
}