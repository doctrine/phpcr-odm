<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Builder;
use Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractLeafNode;

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
        $refl = new \ReflectionClass($expectedClass);

        if ($refl->isSubclassOf(
            'Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractLeafNode'
        )) {
            $this->assertSame($this->node, $res);
        } else {
            $this->assertInstanceOf($expectedClass, $res);
            $parent = $res->getParent();
            $this->assertSame($this->node, $parent);
        }
    }
}



