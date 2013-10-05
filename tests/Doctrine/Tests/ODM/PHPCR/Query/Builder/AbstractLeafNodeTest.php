<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

class AbstractLeafNodeTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->leafNode = $this->getMockBuilder(
            'Doctrine\ODM\PHPCR\Query\Builder\AbstractLeafNode'
        )->getMockForAbstractClass();

        $this->refl = new \ReflectionClass($this->leafNode);
    }

    public function provideTestExplodeField()
    {
        return array(
            array('foo.bar', false, array('foo', 'bar')),
            array('foobar', 'Invalid field specification'),
            array('foobar.foobar.foobar', 'Invalid field specification'),
        );
    }

    /**
     * @dataProvider provideTestExplodeField
     */
    public function testExplodeField($fieldSpec, $xpctdExceptionMessage, $xpctdRes = array())
    {
        if ($xpctdExceptionMessage) {
            $this->setExpectedException('Doctrine\ODM\PHPCR\Exception\RuntimeException', $xpctdExceptionMessage);
        }

        $method = $this->refl->getMethod('explodeField');
        $method->setAccessible(true);
        $res = $method->invoke($this->leafNode, $fieldSpec);

        if (false === $xpctdExceptionMessage) {
            $this->assertCount(2, $res);
            $this->assertEquals($xpctdRes[0], $res[0]);
            $this->assertEquals($xpctdRes[1], $res[1]);
        }
    }
}
