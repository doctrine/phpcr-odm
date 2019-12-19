<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use PHPUnit\Framework\TestCase;

abstract class LeafNodeTestCase extends TestCase
{
    /**
     * @var AbstractNode
     */
    protected $parent;

    public function setUp(): void
    {
        $this->parent = $this->getMockBuilder(AbstractNode::class)
            ->setMockClassName('ParentNode')
            ->getMockForAbstractClass();
    }

    /**
     * @dataProvider provideNode
     */
    public function testNode($type, $args, $methods)
    {
        $ns = 'Doctrine\ODM\PHPCR\Query\Builder';
        $fqn = $ns.'\\'.$type;

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
