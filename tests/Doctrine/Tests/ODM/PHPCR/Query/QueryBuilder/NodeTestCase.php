<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Builder;
use Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractLeafNode;

abstract class NodeTestCase extends \PHPUnit_Framework_TestCase
{
    protected $node;

    public function setUp()
    {
        $this->parent = $this->getMockBuilder(
            'Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractNode'
        )->setMockClassName('ParentNode')->getMockForAbstractClass();
        $this->node = $this->getNode();
    }

    abstract public function provideInterface();

    public function getNode($args = array())
    {
        $refl = new \ReflectionClass($this);
        preg_match('&^(.*?)Test&', $refl->getShortName(), $matches);
        $nodeClass = $matches[1];
        $fqn = 'Doctrine\\ODM\\PHPCR\Query\\QueryBuilder\\'.$nodeClass;
        $nodeRefl = new \ReflectionClass($fqn);
        $inst = $nodeRefl->newInstanceArgs(array_merge(array(
            $this->parent
        ), $args));

        return $inst;
    }

    /**
     * @dataProvider provideInterface
     */
    public function testInterface($method, $type, $args = array())
    {
        $expectedClass = 'Doctrine\\ODM\\PHPCR\\Query\\QueryBuilder\\'.$type;

        $res = call_user_func_array(array($this->node, $method), $args);
        $refl = new \ReflectionClass($expectedClass);

        if ($refl->isSubclassOf(
            'Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractLeafNode'
        )) {
            $this->assertSame($this->node, $res, 'Leaf node method "'.$method.'" returns parent');
        } else {
            $this->assertInstanceOf($expectedClass, $res);
            $parent = $res->getParent();
            $this->assertSame($this->node, $parent);
        }
    }
}
