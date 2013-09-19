<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

class AbstractNodeTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->parent = $this->getMockBuilder(
            'Doctrine\ODM\PHPCR\Query\Builder\AbstractNode'
        )->setMockClassName('ParentNode')->getMockForAbstractClass();

        $this->node1 = $this->getMockBuilder('Doctrine\ODM\PHPCR\Query\Builder\AbstractNode')
            ->setMockClassName('TestNode')
            ->setConstructorArgs(array($this->parent))
            ->getMockForAbstractClass();

        $this->leafNode = $this->getMockBuilder('Doctrine\ODM\PHPCR\Query\Builder\AbstractLeafNode')
            ->setMockClassName('LeafNode')
            ->setConstructorArgs(array($this->node1))
            ->getMockForAbstractClass();
        $this->leafNode->expects($this->any())
            ->method('getNodeType')
            ->will($this->returnValue('LeafNode'));
    }

    protected function addChildrenToNode1($data)
    {
        foreach ($data as $className) {
            $childNode = $this->getMockForAbstractClass(
                'Doctrine\ODM\PHPCR\Query\Builder\AbstractNode',
                array(),
                $className
            );
            $childNode->expects($this->once())
                ->method('getNodeType')
                ->will($this->returnValue($className));

            $res = $this->node1->addChild($childNode);
            $this->assertSame($childNode, $res);
        }
    }

    public function testGetName()
    {
        $res = $this->node1->getName();
        $this->assertEquals('TestNode', $res);
    }

    public function provideAddChildValidation()
    {
        return array(
            // 1. Foobar bounded 1..1
            // VALID: We fulful criteria
            array(
                array(
                    'FooBar' => array(1, 1),
                ), 
                array(
                    'FooBar',
                ),
                array(
                ),
            ),
            // 1a. 
            // INVALID: Out of bounds
            array(
                array(
                    'FooBar' => array(1, 1),
                ), 
                array(
                    'FooBar',
                    'FooBar',
                ), 
                array(
                    'exceeds_max' => true
                ),
            ),
            // 1b.
            // INVALID: Wrong child type
            array(
                array(
                    'FooBar' => array(1, 1),
                ), 
                array(
                    'BarFoo',
                ), 
                array(
                    'invalid_child' => true
                ),
            ),
            // 2. FooBar bounded below, unbounded above
            array(
                array(
                    'FooBar' => array(1, null),
                ), 
                array(
                    'FooBar',
                    'FooBar',
                    'FooBar',
                    'FooBar',
                ), 
                array(
                ),
            ),
            // 2a.
            // INVALID: third datum is of wrong type
            array(
                array(
                    'FooBar' => array(1, null),
                ), 
                array(
                    'FooBar',
                    'FooBar',
                    'BarFoo',
                ), 
                array(
                    'invalid_child' => true,
                ),
            ),
        );
    }

    /**
     * @dataProvider provideAddChildValidation
     */
    public function testAddChildValidation($cardinalityMap, $data, $options)
    {
        $options = array_merge(array(
            'exceeds_max' => false,
            'invalid_child' => false,
        ), $options);

        $this->node1->expects($this->any())
            ->method('getCardinalityMap')
            ->will($this->returnValue($cardinalityMap));

        if ($options['exceeds_max']) {
            $this->setExpectedException('OutOfBoundsException', 'cannot exceed');
        }

        if ($options['invalid_child']) {
            $this->setExpectedException('OutOfBoundsException', 'cannot be appended');
        }

        $this->addChildrenToNode1($data);
        $this->assertCount(count($data), $this->node1->getChildren());
    }

    public function testAddChildLeaf()
    {
        $this->node1->expects($this->any())
            ->method('getCardinalityMap')
            ->will($this->returnValue(array(
                'LeafNode' => array(1, 1),
            )));

        $res = $this->node1->addChild($this->leafNode);
        $this->assertSame($this->node1, $res);
    }

    public function provideValidate()
    {
        return array(

            // 1. Not enough data
            // INVALID
            array(
                array(
                    'FooBar' => array(1, 1),
                ),
                array(
                ), // not data!
                array(
                    'expected_exception' => array(
                        'OutOfBoundsException',
                        '"TestNode" must have at least "1" child nodes of type "FooBar". "0" given'
                    ),
                )
            ),

            // 1a.
            // INVALID
            array(
                array(
                    'FooBar' => array(3, 3),
                ),
                array(
                    'FooBar',
                    'FooBar',
                ),
                array(
                    'expected_exception' => array(
                        'OutOfBoundsException',
                        '"TestNode" must have at least "3" child nodes of type "FooBar". "2" given'
                    ),
                )
            ),
        );
    }

    /**
     * @depends testAddChildValidation
     * @dataProvider provideValidate
     */
    public function testValidate($cardinalityMap, $data, $options)
    {
        $options = array_merge(array(
            'invalid_child' => false,
            'expected_exception' => false,
        ), $options);

        if ($options['expected_exception']) {
            list($exceptionType, $exceptionMessage) = $options['expected_exception'];
            $this->setExpectedException($exceptionType, $exceptionMessage);
        }

        $this->node1->expects($this->any())
            ->method('getCardinalityMap')
            ->will($this->returnValue($cardinalityMap));

        $this->addChildrenToNode1($data);
        $this->node1->validate();
    }

    public function testSetChild()
    {
        $this->parent->expects($this->any())
            ->method('getCardinalityMap')
            ->will($this->returnValue(array(
                'foo' => array(1, 2),
                'bar' => array(1, 2),
            )));

        $this->node1->expects($this->any())
            ->method('getNodeType')
            ->will($this->onConsecutiveCalls(
                'foo', 'foo', 'bar', 'foo', 'foo'
            ));

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

    public function testEnd()
    {
        $this->node1->expects($this->any())
            ->method('getCardinalityMap')
            ->will($this->returnValue(array()));

        $res = $this->node1->end();
        $this->assertSame($this->parent, $res);
    }
}
