<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Builder;
use Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractLeafNode;

abstract class LeafNodeTestCase extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->parent = $this->getMockBuilder(
            'Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractNode'
        )->setMockClassName('ParentNode')->getMockForAbstractClass();
    }

    /**
     * @dataProvider provideNode
     */
    public function testNode($type, $args, $methods)
    {
        $ns = 'Doctrine\ODM\PHPCR\Query\QueryBuilder';
        $fqn = $ns . '\\' . $type;

        $values = array_values($args);
        array_unshift($values, $this->parent);

        $refl = new \ReflectionClass($fqn);
        $instance = $refl->newInstanceArgs($values);

        foreach ($methods as $method => $value) {
            $res = $instance->{$method}();
            $this->assertEquals($value, $res);
        }
    }
}

