<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

class AbstractNodeTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->node1 = $this->getMockBuilder(
            'Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractNode'
        )->setMockClassName('TestNode')->getMockForAbstractClass();
    }

    public function testGetName()
    {
        $res = $this->node1->getName();
        $this->assertEquals('TestNode', $res);
    }

    public function provideAddChild()
    {
        return array(
            array(array(
                'FooBar' => array(1, 1),
            ), array(
                'FooBar',
                'Foobar',
            ), array(
                'exceeds_max' => true
            )
        ));
    }

    /**
     * @dataProvider provideAddChild
     */
    public function testAddChild($cardinalityMap, $data, $options)
    {
        $options = array_merge(array(
            'exceeds_max' => false,
        ), $options);

        $this->node1->expects($this->any())
            ->method('getCardinalityMap')
            ->will($this->returnValue($cardinalityMap));

        if ($options['exceeds_max']) {
            $this->setExpectedException('OutOfBoundsException');
        }

        foreach ($data as $className) {
            $childNode = $this->getMockForAbstractClass(
                'Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractNode',
                array(),
                $className
            );

            $this->node1->addChild($childNode);
        }
    }
}

