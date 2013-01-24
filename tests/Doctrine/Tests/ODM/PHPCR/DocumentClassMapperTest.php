<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\DocumentClassMapper;
use Jackalope\Node;
use PHPCR\PropertyType;

class DocumentClassMapperTest extends \PHPUnit_Framework_Testcase
{
    const CLASS_GENERIC = 'Doctrine\ODM\PHPCR\Document\Generic';
    const CLASS_TEST_1 = 'Test\Class1';
    const CLASS_TEST_2 = 'Test\Class2';
    const CLASS_TEST_3 = 'Test\Class3';

    public function setUp()
    {
        $this->dm = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
          ->disableOriginalConstructor()
          ->getMock();

        // This should be PHPCR\NodeInterface but as of time of writing PHPUnit
        // will not Mock Traversable interfaces: 
        // https://github.com/sebastianbergmann/phpunit-mock-objects/issues/103
        $this->node = $this->getMockBuilder('Jackalope\Node')
          ->disableOriginalConstructor()
          ->getMock();

        $this->cmd = $this->getMockBuilder('Doctrine\ODM\PHPCR\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dcm = new DocumentClassMapper();
    }

    public function testWriteMetadataWhenClassIsGeneric()
    {
        $this->node->expects($this->never())
            ->method('setProperty');
        $this->dcm->writeMetadata($this->dm, $this->node, self::CLASS_GENERIC);
    }

    public function testWriteMetadata()
    {
        $parentClasses = array(self::CLASS_TEST_2, self::CLASS_TEST_3);

        $this->node->expects($this->at(0))
            ->method('setProperty')
            ->with('phpcr:class', self::CLASS_TEST_1, PropertyType::STRING);

        $this->dm->expects($this->once())
            ->method('getClassMetadata')
            ->with(self::CLASS_TEST_1)
            ->will($this->returnValue($this->cmd));

        $this->cmd->expects($this->once())
            ->method('getParentClasses')
            ->will($this->returnValue($parentClasses));

        // Assert that we set the correct parent classes
        $this->node->expects($this->at(1))
            ->method('setProperty')
            ->with('phpcr:classparents', $parentClasses, PropertyType::STRING);

        $this->dcm->writeMetadata($this->dm, $this->node, self::CLASS_TEST_1);
    }
}
