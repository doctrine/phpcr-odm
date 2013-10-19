<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;

abstract class LeafNodeTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractNode
     */
    protected $parent;

    public function setUp()
    {
        $this->parent = $this->getMockBuilder(
            'Doctrine\ODM\PHPCR\Query\Builder\AbstractNode'
        )->setMockClassName('ParentNode')->getMockForAbstractClass();
    }

    /**
     * @dataProvider provideNode
     */
    public function testNode($type, $args, $methods)
    {
        $ns = 'Doctrine\ODM\PHPCR\Query\Builder';
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

