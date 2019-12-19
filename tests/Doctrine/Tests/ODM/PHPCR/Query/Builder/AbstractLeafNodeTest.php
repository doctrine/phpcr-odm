<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Exception\RuntimeException;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractLeafNode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractLeafNodeTest extends TestCase
{
    /**
     * @var AbstractLeafNode|MockObject
     */
    private $leafNode;

    /**
     * @var \ReflectionClass
     */
    private $refl;

    public function setUp(): void
    {
        $this->leafNode = $this->createMock(AbstractLeafNode::class);

        $this->refl = new \ReflectionClass($this->leafNode);
    }

    public function provideTestExplodeField()
    {
        return [
            ['foo.bar', false, ['foo', 'bar']],
            ['foobar', 'Invalid field specification'],
            ['foobar.foobar.foobar', 'Invalid field specification'],
        ];
    }

    /**
     * @dataProvider provideTestExplodeField
     */
    public function testExplodeField($fieldSpec, $xpctdExceptionMessage, $xpctdRes = [])
    {
        if ($xpctdExceptionMessage) {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($xpctdExceptionMessage);
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
