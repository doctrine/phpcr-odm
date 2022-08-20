<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\AbstractLeafNode;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use PHPUnit\Framework\TestCase;

class AbstractNodeTest extends TestCase
{
    private $parent;

    private $node1;

    private $leafNode;

    public function setUp(): void
    {
        $this->parent = $this->getMockBuilder(
            AbstractNode::class
        )->setMockClassName('ParentNode')->getMockForAbstractClass();

        $this->node1 = $this->getMockBuilder(AbstractNode::class)
            ->setMockClassName('TestNode')
            ->setConstructorArgs([$this->parent])
            ->getMockForAbstractClass();

        $this->leafNode = $this->getMockBuilder(AbstractLeafNode::class)
            ->setMockClassName('LeafNode')
            ->setConstructorArgs([$this->node1])
            ->getMockForAbstractClass();
        $this->leafNode
            ->method('getNodeType')
            ->willReturn('LeafNode');
    }

    protected function addChildrenToNode1($data): void
    {
        foreach ($data as $className) {
            $childNode = $this->getMockForAbstractClass(
                AbstractNode::class,
                [],
                $className
            );
            $childNode->expects($this->once())
                ->method('getNodeType')
                ->willReturn($className);

            $res = $this->node1->addChild($childNode);
            $this->assertSame($childNode, $res);
        }
    }

    public function testGetName(): void
    {
        $res = $this->node1->getName();
        $this->assertEquals('TestNode', $res);
    }

    public function provideAddChildValidation(): array
    {
        return [
            // 1. Foobar bounded 1..1
            // VALID: We fulful criteria
            [
                [
                    'FooBar' => [1, 1],
                ],
                [
                    'FooBar',
                ],
                [
                ],
            ],
            // 1a.
            // INVALID: Out of bounds
            [
                [
                    'FooBar' => [1, 1],
                ],
                [
                    'FooBar',
                    'FooBar',
                ],
                [
                    'exceeds_max' => true,
                ],
            ],
            // 1b.
            // INVALID: Wrong child type
            [
                [
                    'FooBar' => [1, 1],
                ],
                [
                    'BarFoo',
                ],
                [
                    'invalid_child' => true,
                ],
            ],
            // 2. FooBar bounded below, unbounded above
            [
                [
                    'FooBar' => [1, null],
                ],
                [
                    'FooBar',
                    'FooBar',
                    'FooBar',
                    'FooBar',
                ],
                [
                ],
            ],
            // 2a.
            // INVALID: third datum is of wrong type
            [
                [
                    'FooBar' => [1, null],
                ],
                [
                    'FooBar',
                    'FooBar',
                    'BarFoo',
                ],
                [
                    'invalid_child' => true,
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideAddChildValidation
     */
    public function testAddChildValidation($cardinalityMap, $data, $options): void
    {
        $options = array_merge([
            'exceeds_max' => false,
            'invalid_child' => false,
        ], $options);

        $this->node1
            ->method('getCardinalityMap')
            ->willReturn($cardinalityMap);

        if ($options['exceeds_max']) {
            $this->expectException(\OutOfBoundsException::class);
            $this->expectExceptionMessage('cannot exceed');
        }

        if ($options['invalid_child']) {
            $this->expectException(\OutOfBoundsException::class);
            $this->expectExceptionMessage('cannot be appended');
        }

        $this->addChildrenToNode1($data);
        $this->assertCount(count($data), $this->node1->getChildren());
    }

    public function testAddChildLeaf(): void
    {
        $this->node1
            ->method('getCardinalityMap')
            ->willReturn([
                'LeafNode' => [1, 1],
            ]);

        $res = $this->node1->addChild($this->leafNode);
        $this->assertSame($this->node1, $res);
    }

    public function provideValidate(): array
    {
        return [
            // 1. Not enough data
            // INVALID
            [
                [
                    'FooBar' => [1, 1],
                ],
                [
                ], // not data!
                [
                    'expected_exception' => [
                        'OutOfBoundsException',
                        '"TestNode" must have at least "1" child nodes of type "FooBar". "0" given',
                    ],
                ],
            ],

            // 1a.
            // INVALID
            [
                [
                    'FooBar' => [3, 3],
                ],
                [
                    'FooBar',
                    'FooBar',
                ],
                [
                    'expected_exception' => [
                        'OutOfBoundsException',
                        '"TestNode" must have at least "3" child nodes of type "FooBar". "2" given',
                    ],
                ],
            ],
        ];
    }

    /**
     * @depends testAddChildValidation
     * @dataProvider provideValidate
     */
    public function testValidate($cardinalityMap, $data, $options): void
    {
        $options = array_merge([
            'invalid_child' => false,
            'expected_exception' => false,
        ], $options);

        if ($options['expected_exception']) {
            list($exceptionType, $exceptionMessage) = $options['expected_exception'];
            $this->expectException($exceptionType);
            $this->expectExceptionMessage($exceptionMessage);
        }

        $this->node1
            ->method('getCardinalityMap')
            ->willReturn($cardinalityMap);

        $this->addChildrenToNode1($data);
        $this->node1->validate();
    }

    public function testSetChild(): void
    {
        $this->parent
            ->method('getCardinalityMap')
            ->willReturn([
                'foo' => [1, 2],
                'bar' => [1, 2],
            ]);

        $this->node1
            ->method('getNodeType')
            ->willReturnOnConsecutiveCalls(
                'foo',
                'foo',
                'bar',
                'foo',
                'foo'
            );

        $this->parent->addChild($this->node1);
        $this->parent->addChild($this->node1);
        $this->parent->addChild($this->node1);

        $this->assertCount(3, $this->parent->getChildren());
        $this->assertCount(2, $this->parent->getChildrenOfType('foo'));

        // set child removes 2 existing "foo" nodes and adds one
        $this->parent->setChild($this->node1);
        $this->assertCount(2, $this->parent->getChildren());
        $this->assertCount(1, $this->parent->getChildrenOfType('foo'));
    }

    public function testEnd(): void
    {
        $this->node1
            ->method('getCardinalityMap')
            ->willReturn([]);

        $res = $this->node1->end();
        $this->assertSame($this->parent, $res);
    }
}
