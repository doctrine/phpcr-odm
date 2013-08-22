<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Builder;
use Jackalope\Query\QOM\QueryObjectModelFactory;
use Doctrine\ODM\PHPCR\Query\QueryBuilder\BuilderConverterPhpcr;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;

class BuilderConverterPhpcrTest extends \PHPUnit_Framework_TestCase
{
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

    /**
     * Return a builder with a source, as a selector is required for
     * all methods
     */
    protected function primeBuilder()
    {
        $from = $this->qb->from()->document('foobar', 'sel_1');
        $res = $this->converter->dispatch($from);
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

    public function provideDispatchFromJoin()
    {
        return array(
            // join types
            array('joinInner', QOMConstants::JCR_JOIN_TYPE_INNER),
            array('joinLeftOuter', QOMConstants::JCR_JOIN_TYPE_LEFT_OUTER),
            array('joinRightOuter', QOMConstants::JCR_JOIN_TYPE_RIGHT_OUTER),
            array('joinInner', QOMConstants::JCR_JOIN_TYPE_INNER),

            // conditions
            array('joinInner', QOMConstants::JCR_JOIN_TYPE_INNER, 'childDocument'),
            array('joinInner', QOMConstants::JCR_JOIN_TYPE_INNER, 'descendant'),
            array('joinInner', QOMConstants::JCR_JOIN_TYPE_INNER, 'sameDocument'),
        );
    }

    /**
     * @dataProvider provideDispatchFromJoin
     */
    public function testDispatchFromJoin($method, $type, $joinCond = null)
    {
        $n = $this->qb->from()
            ->$method()
                ->left()->document('foobar', 'selector_1')->end()
                ->right()->document('barfoo', 'selector_2')->end();

        switch ($joinCond) {
            case 'childDocument':
                $n->condition()->childDocument('child_selector_name', 'parent_selector_name')->end();
                break;
            case 'descendant':
                $n->condition()->descendant('descendant_selector_name', 'ancestor_selector_name')->end();
                break;
            case 'sameDocument':
                $n->condition()->sameDocument('selector_1_name', 'selector_2_name', '/selector2/path')->end();
                break;
            case 'equi':
            default:
                $n->condition()->equi('prop_1', 'selector_1', 'prop_2', 'selector_2')->end();
        }

        $n->end();


        $from = $this->qb->getChildOfType('From');
        $res = $this->converter->dispatch($from);

        $this->assertInstanceOf('PHPCR\Query\QOM\JoinInterface', $res);
        $this->assertEquals($type, $res->getJoinType());
        $this->assertInstanceOf('PHPCR\Query\QOM\SelectorInterface', $res->getLeft());
        $this->assertInstanceOf('PHPCR\Query\QOM\SelectorInterface', $res->getLeft());
    }

    public function testDispatchSelect()
    {
        $this->primeBuilder();

        $select = $this->qb
            ->select()
                ->property('prop_1', 'sel_1')
                ->property('prop_2', 'sel_1');

        $res = $this->converter->dispatch($select);

        $this->assertCount(2, $res);
        $this->assertInstanceOf('PHPCR\Query\QOM\ColumnInterface', $res[0]);
        $this->assertEquals('prop_1_phpcr', $res[0]->getPropertyName());
        $this->assertEquals('prop_1_phpcr', $res[0]->getColumnName());
        $this->assertEquals('prop_2_phpcr', $res[1]->getPropertyName());
        $this->assertEquals('prop_2_phpcr', $res[1]->getColumnName());
    }
}
