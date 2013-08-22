<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Builder;
use Jackalope\Query\QOM\QueryObjectModelFactory;
use Doctrine\ODM\PHPCR\Query\QueryBuilder\BuilderConverterPhpcr;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class BuilderConverterPhpcrTest extends \PHPUnit_Framework_TestCase
{
    const PROPERTY_1_NAME = 'prop_1';

    public function setUp()
    {
        $that = $this;
        // note: this "factory" seems unnecessary in current jackalope
        //       implementation
        $factory = $this->getMock('Jackalope\FactoryInterface');

        // todo: we should have an implementation neutral test model here
        $qomf = new QueryObjectModelFactory($factory);

        $mdf = $this->getMockBuilder(
            'Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory'
        )->disableOriginalConstructor()->getMock();

        $mdf->expects($this->any())
            ->method('getMetadataFor')
            ->will($this->returnCallback(function ($documentFqn) use ($that) {
                $meta = $that->getMockBuilder(
                    'Doctrine\ODM\PHPCR\Mapping\ClassMetadata'
                )->disableOriginalConstructor()->getMock();

                $meta->expects($this->any())
                    ->method('getField')
                    ->will($this->returnCallback(function ($name) {
                        $res = array(
                            'fieldName' => $name,
                            'property' => $name.'_phpcr',
                            'type' => 'String',
                        );
                        return $res;
                    }));

                $meta->expects($this->any())
                    ->method('getNodeType')
                    ->will($this->returnValue('nt:unstructured'));

                return $meta;
            }));
    
        $this->converter = new BuilderConverterPhpcr($mdf, $qomf);
        $this->qb = new Builder;
    }

    public function testDispatchFrom()
    {
        $this->qb->from()->document('foobar', 'selector_name')->end();
        $from = $this->qb->getChildOfType('From');
        $res = $this->converter->dispatch($from);

        $this->assertInstanceOf('PHPCR\Query\QOM\SelectorInterface', $res);
        $this->assertEquals('nt:unstructured', $res->getNodeTypeName());
        $this->assertEquals('selector_name', $res->getSelectorName());
    }

    public function testDispatchFromJoinInner()
    {
        $this->qb->from()
            ->joinInner()
                ->left()->document('foobar', 'selector_1')->end()
                ->right()->document('barfoo', 'selector_2')->end()
                ->condition()->equi('prop_1', 'selector_1', 'prop_2', 'selector_2')->end()
            ->end();

        $from = $this->qb->getChildOfType('From');
        $res = $this->converter->dispatch($from);

        $this->assertInstanceOf('PHPCR\Query\QOM\JoinInterface', $res);
    }
}
