<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Builder;

abstract class NodeTestCase extends \PHPUnit_Framework_TestCase
{
    protected $node;

    public function setUp()
    {
        $this->node = $this->getNode();
    }

    abstract public function provideInterface();

    /**
     * @dataProvider provideInterface
     */
    public function testInterface($method, $expectedClass, $args = array())
    {
        $res = call_user_func_array(array($this->node, $method), $args);

        // only leaf nodes have arguments, and leaf nodes return the parent
        if ($args) {
            $this->assertSame($this->node, $res);
        } else {
            $this->assertInstanceOf($expectedClass, $res);
            $parent = $res->getParent();
            $this->assertSame($this->node, $parent);
        }
    }
}



