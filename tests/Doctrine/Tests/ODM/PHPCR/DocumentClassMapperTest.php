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

        // This should be PHPCR\NodeInterface but as of time of writing PHPCR
        // will not Mock Traversable interfaces: 
        // https://github.com/sebastianbergmann/phpunit-mock-objects/issues/103
        $this->node = $this->getMockBuilder('Jackalope\Node')
          ->disableOriginalConstructor()
          ->getMock();

        $this->cmd = $this->getMockBuilder('Doctrine\ODM\PHPCR\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->refl = $this->getMockBuilder('\ReflectionClass')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mdf = $this->getMockBuilder('Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory')
          ->disableOriginalConstructor()
          ->getMock();

        $this->mappingException = $this->getMock('Doctrine\ODM\PHPCR\Mapping\MappingException');

        $this->dcm = new DocumentClassMapper();
    }

    public function testWriteMetadata_classNotGeneric()
    {
        $this->node->expects($this->never())
            ->method('setProperty');
        $this->dcm->writeMetadata($this->dm, $this->node, self::CLASS_GENERIC);
    }

    public function getWriteMetadataTests()
    {
        return array(
            array(
                // 2 mapped parent classes
                array(self::CLASS_TEST_2 => true, self::CLASS_TEST_3 => true),
            ),
            array(
                // mapped parent class and non-mapped parent class
                array(self::CLASS_TEST_2 => true, self::CLASS_TEST_3 => false),
            ),
            array(
                // both not mapped
                array(self::CLASS_TEST_2 => false, self::CLASS_TEST_3 => false),
            ),
            array(
                // first not mapped, but second is.
                array(self::CLASS_TEST_2 => false, self::CLASS_TEST_3 => true),
            ),
        );
    }

    /**
     * @dataProvider getWriteMetadataTests
     */
    public function testWriteMetadata($classesToMapped)
    {
        $this->node->expects($this->at(0))
            ->method('setProperty')
            ->with('phpcr:class', self::CLASS_TEST_1, PropertyType::STRING);

        $expectedClasses = array();
        foreach ($classesToMapped as $className => $isMapped) {
            if ($isMapped) {
                $expectedClasses[] = $className;
            }
        }

        // Assert that we set the correct parent classes
        $this->node->expects($this->at(1))
            ->method('setProperty')
            ->with('phpcr:classparents', $expectedClasses, PropertyType::STRING);

        $this->dm->expects($this->once())
            ->method('getClassMetadata')
            ->will($this->returnValue($this->cmd));

        $this->dm->expects($this->once())
            ->method('getMetadataFactory')
            ->will($this->returnValue($this->mdf));

        $this->cmd->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue($this->refl));

        $reflInvkCount = 0;
        $factInvkCount = 0;

        foreach ($classesToMapped as $className => $isMapped) {
            $this->refl->expects($this->at($reflInvkCount++))
                ->method('getParentClass')
                ->will($this->returnValue($this->refl));

            if ($isMapped) {
                $this->refl->expects($this->at($reflInvkCount++))
                    ->method('getName')
                    ->will($this->returnValue($className));
                $this->mdf->expects($this->at($factInvkCount++))
                    ->method('getMetadataFor');
            } else {
                $this->mdf->expects($this->at($factInvkCount++))
                    ->method('getMetadataFor')
                    ->will($this->throwException($this->mappingException));
            }
        }

        $this->dcm->writeMetadata($this->dm, $this->node, self::CLASS_TEST_1);
    }
}
