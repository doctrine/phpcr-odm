<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Document\Generic;
use Doctrine\ODM\PHPCR\DocumentClassMapper;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Exception\ClassMismatchException;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\UnitOfWork;
use Jackalope\Node;
use Jackalope\Property;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DocumentClassMapperTest extends Testcase
{
    const CLASS_GENERIC = Generic::class;

    const CLASS_TEST_1 = 'Test\Class1';

    const CLASS_TEST_2 = 'Test\Class2';

    const CLASS_TEST_3 = 'Test\Class3';

    /**
     * @var DocumentManager&MockObject
     */
    private $dm;

    /**
     * @var NodeInterface&MockObject
     */
    private $node;

    /**
     * @var ClassMetadata&MockObject
     */
    private $metadata;

    /**
     * @var DocumentClassMapper
     */
    private $mapper;

    public function setUp(): void
    {
        $this->dm = $this->createMock(DocumentManager::class);

        // This should be PHPCR\NodeInterface but as of time of writing PHPUnit
        // will not Mock Traversable interfaces:
        // https://github.com/sebastianbergmann/phpunit-mock-objects/issues/103
        $this->node = $this->createMock(Node::class);

        $this->metadata = $this->createMock(ClassMetadata::class);

        $this->mapper = new DocumentClassMapper();
    }

    public function testGetClassNameGeneric(): void
    {
        $className = $this->mapper->getClassName($this->dm, $this->node);

        $this->assertEquals(
            Generic::class,
            $className
        );
    }

    /**
     * The node has no class information on it whatsoever.
     */
    public function testGetClassNameOnlySpecified(): void
    {
        $className = $this->mapper->getClassName($this->dm, $this->node, BaseClass::class);

        $this->assertEquals(
            BaseClass::class,
            $className
        );
    }

    /**
     * Make the mock node behave as having a phpcr:class property set.
     *
     * @param string $class The phpcr:class value to use
     */
    private function mockNodeHasClass($class): void
    {
        $property = $this->createMock(Property::class);
        $property->expects($this->once())
            ->method('getString')
            ->willReturn($class);
        $this->node->expects($this->once())
            ->method('hasProperty')
            ->with('phpcr:class')
            ->willReturn(true);
        $this->node->expects($this->once())
            ->method('getProperty')
            ->with('phpcr:class')
            ->willReturn($property);
    }

    public function testGetClassNameNull(): void
    {
        $this->mockNodeHasClass(BaseClass::class);
        $className = $this->mapper->getClassName($this->dm, $this->node);

        $this->assertEquals(
            BaseClass::class,
            $className
        );
    }

    public function testGetClassNameMatch(): void
    {
        $this->mockNodeHasClass(BaseClass::class);

        $className = $this->mapper->getClassName($this->dm, $this->node, BaseClass::class);

        $this->assertEquals(
            BaseClass::class,
            $className
        );
    }

    public function testGetClassNameExtend(): void
    {
        $this->mockNodeHasClass(ExtendingClass::class);

        $className = $this->mapper->getClassName($this->dm, $this->node, BaseClass::class);

        $this->assertEquals(ExtendingClass::class, $className);
    }

    public function testGetClassNameMismatch(): void
    {
        $this->mockNodeHasClass(BaseClass::class);

        $this->expectException(ClassMismatchException::class);
        $this->mapper->getClassName($this->dm, $this->node, ExtendingClass::class);
    }

    public function testWriteMetadataWhenClassIsGeneric(): void
    {
        $this->node->expects($this->never())
            ->method('setProperty');
        $this->mapper->writeMetadata($this->dm, $this->node, self::CLASS_GENERIC);
    }

    public function testWriteMetadata(): void
    {
        $parentClasses = [self::CLASS_TEST_2, self::CLASS_TEST_3];

        $this->node->expects($this->at(0))
            ->method('setProperty')
            ->with('phpcr:class', self::CLASS_TEST_1, PropertyType::STRING);

        $this->dm->expects($this->once())
            ->method('getClassMetadata')
            ->with(self::CLASS_TEST_1)
            ->willReturn($this->metadata);

        $this->metadata->expects($this->once())
            ->method('getParentClasses')
            ->willReturn($parentClasses);

        // Assert that we set the correct parent classes
        $this->node->expects($this->at(1))
            ->method('setProperty')
            ->with('phpcr:classparents', $parentClasses, PropertyType::STRING);

        $this->mapper->writeMetadata($this->dm, $this->node, self::CLASS_TEST_1);
    }

    public function testValidateClassNameValid(): void
    {
        $generic = new Generic();
        $this->mapper->validateClassName($this->dm, $generic, get_class($generic));

        $this->addToAssertionCount(1);
    }

    public function testValidateClassNameInvalid(): void
    {
        $generic = new Generic();
        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->once())
            ->method('determineDocumentId')
            ->with($generic)
            ->willReturn('/id');
        $this->dm->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $this->expectException(ClassMismatchException::class);
        $this->mapper->validateClassName($this->dm, $generic, 'Other\Class');
    }

    public function provideExpandClassName(): array
    {
        return [
            ['Foobar/BarFoo/Document/Foobar', 'Foobar/BarFoo/Document/Foobar', false],
            ['Foobar:Barfoo', 'Foobar\Barfoo', true],
        ];
    }

    /**
     * @dataProvider provideExpandClassName
     */
    public function testExpandClassName($className, $fqClassName, $isAlias): void
    {
        if ($isAlias) {
            $this->dm->expects($this->once())
                ->method('getClassMetadata')
                ->with($className)
                ->willReturn($this->metadata);
            $this->metadata->expects($this->once())
                ->method('getName')
                ->willReturn($fqClassName);
        }

        $refl = new \ReflectionClass($this->mapper);
        $method = $refl->getMethod('expandClassName');
        $method->setAccessible(true);
        $res = $method->invoke($this->mapper, $this->dm, $className);

        $this->assertEquals($fqClassName, $res);
    }
}

class BaseClass
{
}

class ExtendingClass extends BaseClass
{
}
