<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\AbstractLeafNode;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use PHPUnit\Framework\TestCase;

abstract class NodeTestCase extends TestCase
{
    protected $node;

    public function setUp()
    {
        $this->parent = $this->getMockBuilder(AbstractNode::class)
            ->setMockClassName('ParentNode')
            ->getMockForAbstractClass();
        $this->node = $this->getNode();
    }

    abstract public function provideInterface();

    protected function getNode($args = [])
    {
        $refl = new \ReflectionClass($this);
        preg_match('&^(.*?)Test&', $refl->getShortName(), $matches);
        $nodeClass = $matches[1];
        $fqn = 'Doctrine\\ODM\\PHPCR\Query\\Builder\\'.$nodeClass;
        $nodeRefl = new \ReflectionClass($fqn);
        $inst = $nodeRefl->newInstanceArgs(array_merge([
            $this->parent,
        ], $args));

        return $inst;
    }

    /**
     * @dataProvider provideInterface
     */
    public function testInterface($method, $type, $args = [])
    {
        $expectedClass = 'Doctrine\\ODM\\PHPCR\\Query\\Builder\\'.$type;

        $res = call_user_func_array([$this->node, $method], $args);
        $refl = new \ReflectionClass($expectedClass);

        if ($refl->isSubclassOf(AbstractLeafNode::class)) {
            $this->assertSame($this->node, $res, 'Leaf node method "'.$method.'" returns parent');
        } else {
            $this->assertInstanceOf($expectedClass, $res);
            $parent = $res->getParent();
            $this->assertSame($this->node, $parent);
        }
    }
}
