<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\QueryBuilder;

use Doctrine\ODM\PHPCR\Query\QueryBuilder\Builder;
use Jackalope\Query\QOM\QueryObjectModelFactory;
use Doctrine\ODM\PHPCR\Query\QueryBuilder\BuilderConverterPhpcr;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractNode as QBConstants;

class BuilderConverterPhpcrTest extends \PHPUnit_Framework_TestCase
{
    protected $parentNode;

    public function setUp()
    {
        $that = $this;
        // note: this "factory" seems unnecessary in current jackalope
        //       implementation
        $this->qomfFactory = $this->getMock('Jackalope\FactoryInterface');

        $this->qomf = new QueryObjectModelFactory($this->qomfFactory);

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

        $dm = $this->getMockBuilder(
            'Doctrine\ODM\PHPCR\DocumentManager'
        )->disableOriginalConstructor()->getMock();

        $dm->expects($this->once())
            ->method('getMetadataFactory')
            ->will($this->returnValue($mdf));

        $this->parentNode = $this->getMockBuilder('Doctrine\ODM\PHPCR\Query\QueryBuilder\AbstractNode')
            ->disableOriginalConstructor()
            ->getMock();
    
        $this->converter = new BuilderConverterPhpcr($dm, $this->qomf);

        $this->qb = new Builder();
        $this->qb->setConverter($this->converter);
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

    protected function createNode($class, $constructorArgs)
    {
        array_unshift($constructorArgs, $this->parentNode);
        
        $ns = 'Doctrine\\ODM\\PHPCR\\Query\\QueryBuilder';
        $refl = new \ReflectionClass($ns.'\\'.$class);
        $node = $refl->newInstanceArgs($constructorArgs);

        return $node;
    }

    public function testDispatchFrom()
    {
        $from = $this->createNode('From', array());
        $source = $this->createNode('SourceDocument', array('foobar', 'selector_name'));
        $from->addChild($source);

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

        $from = $this->qb->getChildOfType(QBConstants::NT_FROM);
        $res = $this->converter->dispatch($from);

        $this->assertInstanceOf('PHPCR\Query\QOM\JoinInterface', $res);
        $this->assertEquals($type, $res->getJoinType());
        $this->assertInstanceOf('PHPCR\Query\QOM\SelectorInterface', $res->getLeft());
        $this->assertInstanceOf('PHPCR\Query\QOM\SelectorInterface', $res->getLeft());
    }

    /**
     * @depends testDispatchFrom
     */
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

    public function provideDispatchCompositeConstraints()
    {
        return array(
            array('andX', 'PHPCR\Query\QOM\AndInterface'),
            array('orX', 'PHPCR\Query\QOM\OrInterface'),
        );
    }

    /**
     * @depends testDispatchFrom
     * @dataProvider provideDispatchCompositeConstraints
     */
    public function testDispatchCompositeConstraints($method, $expectedClass)
    {
        $this->primeBuilder();

        $where = $this->qb
            ->where()
                ->$method()
                    ->propertyExists('prop_1', 'sel_1')
                    ->propertyExists('prop_2', 'sel_1');

        $res = $this->converter->dispatch($where);

        $this->assertInstanceOf($expectedClass, $res);
        $this->assertInstanceOf('PHPCR\Query\QOM\PropertyExistenceInterface', $res->getConstraint1());
        $this->assertInstanceOf('PHPCR\Query\QOM\PropertyExistenceInterface', $res->getConstraint2());
    }

    public function provideDisaptchConstraintsLeaf()
    {
        return array(
            array(
                'ConstraintPropertyExists', array('prop_1', 'sel_1'), 
                'PropertyExistenceInterface'
            ),
            array(
                'ConstraintFullTextSearch', array('prop_1', 'search_expr', 'sel_1'), 
                'FullTextSearchInterface'
            ),
            array(
                'ConstraintSameDocument', array('/path', 'sel_1'),
                'SameNodeInterface'
            ),
            array(
                'ConstraintDescendantDocument', array('/ancestor/path', 'sel_1'),
                'DescendantNodeInterface'
            ),
            array(
                'ConstraintChildDocument', array('/parent/path', 'sel_1'),
                'ChildNodeInterface'
            ),
        );
    }

    /**
     * @depends testDispatchFrom
     * @dataProvider provideDisaptchConstraintsLeaf
     */
    public function testDispatchConstraintsLeaf($class, $args, $expectedClass)
    {
        $expectedPhpcrClass = '\\PHPCR\\Query\\QOM\\'.$expectedClass;

        $this->primeBuilder();
        $constraint = $this->createNode($class, $args);;
        $res = $this->converter->dispatch($constraint);

        $this->assertInstanceOf($expectedPhpcrClass, $res);
    }

    public function provideDispatchConstraintsComparison()
    {
        return array(
            array('eq', QOMConstants::JCR_OPERATOR_EQUAL_TO),
            array('neq', QOMConstants::JCR_OPERATOR_NOT_EQUAL_TO),
            array('gt', QOMConstants::JCR_OPERATOR_GREATER_THAN),
            array('gte', QOMConstants::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO),
            array('lt', QOMConstants::JCR_OPERATOR_LESS_THAN),
            array('lte', QOMConstants::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO),
            array('like', QOMConstants::JCR_OPERATOR_LIKE),
        );
    }

    /**
     * @dataProvider provideDispatchConstraintsComparison
     */
    public function testDispatchConstraintsComparison($method, $expectedOperator)
    {
        $this->primeBuilder();

        $comparison = $this->qb->where()
            ->$method()
                ->lop()->propertyValue('prop_1', 'sel_1')->end()
                ->rop()->literal('foobar')->end();

        $res = $this->converter->dispatch($comparison);

        $this->assertInstanceOf('PHPCR\Query\QOM\ComparisonInterface', $res);
        $this->assertInstanceOf('PHPCR\Query\QOM\PropertyValueInterface', $res->getOperand1());
        $this->assertInstanceOf('PHPCR\Query\QOM\LiteralInterface', $res->getOperand2());
        $this->assertEquals($expectedOperator, $res->getOperator());
    }

    public function testDispatchConstraintsNot()
    {
        $this->primeBuilder();
        $not = $this->qb->where()
            ->not()->propertyExists('prop_1', 'sel_1');

        $res = $this->converter->dispatch($not);

        $this->assertInstanceOf('PHPCR\Query\QOM\NotInterface', $res);
        $this->assertInstanceOf('PHPCR\Query\QOM\PropertyExistenceInterface', $res->getConstraint());
    }

    public function provideTestDispatchOperands()
    {
        return array(
            // leaf
            array('OperandDynamicDocumentLocalName', array('sel_1'), array(
                'phpcr_class' => 'NodeLocalNameInterface',
                'assert' => function ($test, $node) {
                    $test->assertEquals('sel_1', $node->getSelectorName());
                }
            )),
            array('OperandDynamicDocumentName', array('sel_1'), array(
                'phpcr_class' => 'NodeNameInterface',
                'assert' => function ($test, $node) {
                    $test->assertEquals('sel_1', $node->getSelectorName());
                }
            )),
            array('OperandDynamicFullTextSearchScore', array('sel_1'), array(
                'phpcr_class' => 'FullTextSearchScoreInterface',
                'assert' => function ($test, $node) {
                    $test->assertEquals('sel_1', $node->getSelectorName());
                }
            )),
            array('OperandDynamicLength', array('property_name', 'sel_1'), array(
                'phpcr_class' => 'LengthInterface',
                'assert' => function ($test, $node) {
                    $propertyValue = $node->getPropertyValue();
                    $test->assertInstanceOf(
                        'PHPCR\Query\QOM\PropertyValueInterface',
                        $propertyValue
                    );
                    $test->assertEquals('property_name_phpcr', $propertyValue->getPropertyName());
                    $test->assertEquals('sel_1', $propertyValue->getSelectorName());
                }
            )),
            array('OperandDynamicPropertyValue', array('property_name', 'sel_1'), array(
                'phpcr_class' => 'PropertyValueinterface',
                'assert' => function ($test, $node) {
                    $test->assertEquals('sel_1', $node->getSelectorName());
                    $test->assertEquals('property_name_phpcr', $node->getPropertyName());
                }
            )),

            // non-leaf
            array('OperandDynamicLowerCase', array('sel_1'), array(
                'phpcr_class' => 'LowerCaseInterface',
                'add_child_operand' => true,
                'assert' => function ($test, $node) {
                    $op = $node->getOperand();
                    $this->assertInstanceOf(
                        'PHPCR\Query\QOM\NodeLocalNameInterface',
                        $op
                   );
                }
            )),
            array('OperandDynamicUpperCase', array('sel_1'), array(
                'phpcr_class' => 'UpperCaseInterface',
                'add_child_operand' => true,
                'assert' => function ($test, $node) {
                    $op = $node->getOperand();
                    $this->assertInstanceOf(
                        'PHPCR\Query\QOM\NodeLocalNameInterface',
                        $op
                   );
                }
            )),

            // static
            array('OperandStaticBindVariable', array('variable_name'), array(
                'phpcr_class' => 'BindVariableValueInterface',
                'assert' => function ($test, $node) {
                    $test->assertEquals('variable_name', $node->getBindVariableName());
                }
            )),
            array('OperandStaticLiteral', array('literal_value'), array(
                'phpcr_class' => 'LiteralInterface',
                'assert' => function ($test, $node) {
                    $test->assertEquals('literal_value', $node->getLiteralValue());
                }
            )),
        );

    }

    /**
     * @dataProvider provideTestDispatchOperands
     */
    public function testDispatchOperands($class, $args, $options)
    {
        $options = array_merge(array(
            'phpcr_class' => null,
            'add_child_operand' => false,
            'assert' => null,
        ), $options);

        $expectedPhpcrClass = '\\PHPCR\\Query\\QOM\\'.$options['phpcr_class'];

        $this->primeBuilder();

        $operand = $this->createNode($class, $args);

        if ($options['add_child_operand']) {
            $operand->addChild(
                $this->createNode('OperandDynamicDocumentLocalName', array('sel_1'))
            );
        }

        $res = $this->converter->dispatch($operand);
        $this->assertInstanceOf($expectedPhpcrClass, $res);

        if (null !== $options['assert']) {
            $me = $this;
            $options['assert']($me, $res);
        }
    }

    public function testOrderBy()
    {
        $order1 = $this->createNode('Ordering', array(QOMConstants::JCR_ORDER_ASCENDING));
        $order2 = $this->createNode('Ordering', array(QOMConstants::JCR_ORDER_ASCENDING));
        $orderBy = $this->createNode('OrderBy', array());
        $orderBy->addChild($order1);
        $orderBy->addChild($order2);

        $op = $this->createNode('OperandDynamicDocumentLocalName', array('sel_1'));
        $order1->addChild($op);
        $order2->addChild($op);

        $res = $this->converter->dispatch($orderBy);
    }

    public function testGetQuery()
    {
        $me = $this;

        // setup the query, depends on the query builder
        // working properly..
        $this->qb->select()
            ->property('foobar', 'sel_1');

        $this->qb->from()->document('Fooar', 'sel_1');
        $this->qb->where()->propertyExists('foobar', 'sel_1');
        $this->qb->orderBy()->ascending()->documentName('sel_1');

        // setup the qomf factory to expect the right parameters for createQuery
        $this->qomfFactory->expects($this->once())
            ->method('get')
            ->will($this->returnCallback(function ($class, $args) use ($me) {
                list($om, $source, $constraint, $orderings, $columns) = $args;
                $me->assertInstanceOf(
                    'PHPCR\Query\QOM\SourceInterface', $source
                );
                $me->assertInstanceOf(
                    'PHPCR\Query\QOM\ConstraintInterface', $constraint
                );
                $me->assertCount(1, $columns);

                $column = $columns[0];
                $me->assertInstanceOf(
                    'PHPCR\Query\QOM\ColumnInterface', $column
                );

                $me->assertCount(1, $orderings);
                $ordering = $orderings[0];
                $me->assertInstanceOf(
                    'PHPCR\Query\QOM\OrderingInterface', $ordering
                );

                $qom = $me->getMock('PHPCR\Query\QOM\QueryObjectModelInterface');
                return $qom;
            }));

        $phpcrQuery = $this->converter->getQuery($this->qb);

        $this->assertInstanceOf(
            'Doctrine\ODM\PHPCR\Query\Query', $phpcrQuery
        );
    }
}
