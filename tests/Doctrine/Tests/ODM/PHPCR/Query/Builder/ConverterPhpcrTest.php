<?php

namespace Doctrine\Tests\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Jackalope\Query\QOM\QueryObjectModelFactory;
use Doctrine\ODM\PHPCR\Query\Builder\ConverterPhpcr;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Jackalope\FactoryInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataFactory;
use Doctrine\ODM\PHPCR\DocumentManager;
use PHPCR\Query\QOM\SelectorInterface;
use PHPCR\Query\QOM\PropertyExistenceInterface;
use PHPCR\Query\QOM\JoinInterface;
use PHPCR\Query\QOM\ColumnInterface;
use PHPCR\Query\QOM\ComparisonInterface;
use PHPCR\Query\QOM\PropertyValueInterface;
use PHPCR\Query\QOM\LiteralInterface;
use PHPCR\Query\QOM\NotInterface;
use PHPCR\Query\QOM\NodeLocalNameInterface;
use PHPCR\Query\QOM\SourceInterface;
use PHPCR\Query\QOM\AndInterface;
use PHPCR\Query\QOM\OrInterface;
use PHPCR\Query\QOM\OrderingInterface;
use PHPCR\Query\QOM\QueryObjectModelInterface;
use Doctrine\ODM\PHPCR\Query\Query;

class ConverterPhpcrTest extends TestCase
{
    /**
     * @var AbstractNode|MockObject
     */
    private $parentNode;

    /**
     * @var FactoryInterface|MockObject
     */
    private $qomfFactory;

    /**
     * @var QueryObjectModelFactory
     */
    private $qomf;

    /**
     * @var ConverterPhpcr
     */
    private $converter;

    /**
     * @var QueryBuilder
     */
    private $qb;

    public function setUp(): void
    {
        $me = $this;
        // note: this "factory" seems unnecessary in current jackalope
        //       implementation
        $this->qomfFactory = $this->createMock(FactoryInterface::class);

        $this->qomf = new QueryObjectModelFactory($this->qomfFactory);

        $mdf = $this->createMock(ClassMetadataFactory::class);

        $mdf->expects($this->any())
            ->method('getMetadataFor')
            ->will($this->returnCallback(function ($documentFqn) use ($me) {
                $meta = $me->createMock(ClassMetadata::class);

                if ($documentFqn === '_document_not_mapped_') {
                    return $meta;
                }

                $meta->expects($me->any())
                    ->method('hasField')
                    ->will($me->returnValue(true));

                $meta->expects($me->any())
                    ->method('getFieldMapping')
                    ->will($me->returnCallback(function ($name) {
                        $res = array(
                            'fieldName' => $name,
                            'property' => $name.'_phpcr',
                            'type' => 'String',
                        );
                        return $res;
                    }));

                $meta->expects($me->any())
                    ->method('getNodeType')
                    ->will($me->returnValue('nt:unstructured'));

                $meta->expects($me->any())
                    ->method('getName')
                    ->will($me->returnValue($documentFqn));

                $meta->expects($me->any())
                    ->method('hasAssociation')
                    ->will($me->returnCallback(function ($field) {
                        if ($field === 'associationfield') {
                            return true;
                        }

                        return false;
                    }));

                $meta->nodename = 'nodenameProperty';
                $meta->name = 'MyClassName';

                return $meta;
            }));

        $dm = $this->createMock(DocumentManager::class);

        $dm->expects($this->once())
            ->method('getMetadataFactory')
            ->will($this->returnValue($mdf));

        $dm->expects($this->any())
            ->method('getLocaleChooserStrategy')
            ->will($this->throwException(new InvalidArgumentException('')));

        $this->parentNode = $this->createMock(AbstractNode::class);

        $this->converter = new ConverterPhpcr($dm, $this->qomf);

        $this->qb = new QueryBuilder();
        $this->qb->setConverter($this->converter);
    }

    /**
     * Return a builder with a source, as a alias is required for
     * all methods
     */
    protected function primeBuilder()
    {
        $from = $this->qb->from('alias_1')->document('foobar', 'alias_1');
        $this->converter->dispatch($from);
    }

    protected function createNode($class, $constructorArgs): AbstractNode
    {
        array_unshift($constructorArgs, $this->parentNode);

        $ns = 'Doctrine\\ODM\\PHPCR\\Query\\Builder';
        $refl = new \ReflectionClass($ns.'\\'.$class);

        return $refl->newInstanceArgs($constructorArgs);
    }

    public function testDispatchFrom()
    {
        $from = $this->createNode('From', array());
        $source = $this->createNode('SourceDocument', array(
            'foobar',
            'alias',
        ));
        $from->addChild($source);

        $res = $this->converter->dispatch($from);

        $this->assertInstanceOf(SelectorInterface::class, $res);
        $this->assertEquals('nt:unstructured', $res->getNodeTypeName());
        $this->assertEquals('alias', $res->getSelectorName());
    }

    public function testDispatchFromNonMapped()
    {
        $from = $this->createNode('From', array());
        $source = $this->createNode('SourceDocument', array(
            '_document_not_mapped_',
            'alias',
        ));
        $from->addChild($source);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('_document_not_mapped_ is not a mapped document');
        $this->converter->dispatch($from);
    }

    public function provideDispatchWheres()
    {
        return array(
            array('And'),
            array('Or'),
            array('And', true),
            array('Or', true),
        );
    }

    /**
     * @depends testDispatchFrom
     * @dataProvider provideDispatchWheres
     */
    public function testDispatchWheres($logicalOp, $skipOriginalWhere = false)
    {
        $this->primeBuilder();

        if ($skipOriginalWhere) {
            $where = $this->createNode('Where'.$logicalOp, array());
        } else {
            $where = $this->createNode('Where', array());
        }

        $constraint = $this->createNode('ConstraintFieldIsset', array(
            'alias_1.foobar',
        ));
        $where->addChild($constraint);

        $res = $this->converter->dispatch($where);

        $this->assertInstanceOf(PropertyExistenceInterface::class, $res);
        $this->assertEquals('alias_1', $res->getSelectorName());
        $this->assertEquals('foobar_phpcr', $res->getPropertyName());

        // test add / or where (see dataProvider)
        $whereCon = $this->createNode('Where'.$logicalOp, array());
        $constraint = $this->createNode('ConstraintFieldIsset', array(
            'alias_1.barfoo',
        ));
        $whereCon->addChild($constraint);

        $res = $this->converter->dispatch($whereCon);

        $this->assertInstanceOf('PHPCR\Query\QOM\\'.$logicalOp.'Interface', $res);
        $this->assertEquals('foobar_phpcr', $res->getConstraint1()->getPropertyName());
        $this->assertEquals('barfoo_phpcr', $res->getConstraint2()->getPropertyName());
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
            array('joinInner', QOMConstants::JCR_JOIN_TYPE_INNER, 'child'),
            array('joinInner', QOMConstants::JCR_JOIN_TYPE_INNER, 'descendant'),
            array('joinInner', QOMConstants::JCR_JOIN_TYPE_INNER, 'sameDocument'),
        );
    }

    /**
     * @dataProvider provideDispatchFromJoin
     */
    public function testDispatchFromJoin($method, $type, $joinCond = null)
    {
        $this->markTestSkipped('Joins temporarily disabled');

        $n = $this->qb->from('alias_1')
            ->$method()
                ->left()->document('foobar', 'alias_1')->end()
                ->right()->document('barfoo', 'alias_2')->end();

        switch ($joinCond) {
            case 'child':
                $n->condition()->child('child_alias', 'parent_alias')->end();
                break;
            case 'descendant':
                $n->condition()->descendant('descendant_alias', 'ancestor_alias')->end();
                break;
            case 'sameDocument':
                $n->condition()->sameDocument('alias_1_name', 'alias_2_name', '/alias2/path')->end();
                break;
            case 'equi':
            default:
                $n->condition()->equi('alias_1.prop_1', 'alias_2.prop_2')->end();
        }

        $n->end();

        $from = $this->qb->getChildOfType(AbstractNode::NT_FROM);
        $res = $this->converter->dispatch($from);

        $this->assertInstanceOf(JoinInterface::class, $res);
        $this->assertEquals($type, $res->getJoinType());
        $this->assertInstanceOf(SelectorInterface::class, $res->getLeft());
        $this->assertInstanceOf(SelectorInterface::class, $res->getLeft());
    }

    /**
     * @depends testDispatchFrom
     */
    public function testDispatchSelect()
    {
        $this->primeBuilder();

        $select = $this->qb
            ->select()
                ->field('alias_1.prop_1')
                ->field('alias_1.prop_2');

        $res = $this->converter->dispatch($select);

        $this->assertCount(2, $res);
        $this->assertInstanceOf(ColumnInterface::class, $res[0]);
        $this->assertEquals('prop_1_phpcr', $res[0]->getPropertyName());
        $this->assertEquals('prop_1_phpcr', $res[0]->getColumnName());
        $this->assertEquals('prop_2_phpcr', $res[1]->getPropertyName());
        $this->assertEquals('prop_2_phpcr', $res[1]->getColumnName());

        $addSelect = $this->qb
            ->addSelect()
                ->field('alias_1.prop_3');

        $res = $this->converter->dispatch($addSelect);
        $this->assertCount(3, $res);
        $this->assertEquals('prop_3_phpcr', $res[2]->getPropertyName());
    }

    public function provideDispatchCompositeConstraints()
    {
        return array(
            array('andX', PropertyExistenceInterface::class, 1),
            array('andX', AndInterface::class, 2),
            array('andX', AndInterface::class, 3),
            array('orX', PropertyExistenceInterface::class, 1),
            array('orX', OrInterface::class, 2),
            array('orX', OrInterface::class, 3),
        );
    }

    /**
     * @depends testDispatchFrom
     * @dataProvider provideDispatchCompositeConstraints
     */
    public function testDispatchCompositeConstraints($method, $expectedClass, $nbConstraints)
    {
        $this->primeBuilder();

        $where = $this->qb->where();
        $composite = $where->$method();

        for ($i = 0; $i < $nbConstraints; $i++) {
            $composite->fieldIsset('alias_1.prop_2');
        }

        $res = $this->converter->dispatch($where);

        $this->assertInstanceOf($expectedClass, $res);

        if ($nbConstraints == 2) {
            $this->assertInstanceOf(PropertyExistenceInterface::class, $res->getConstraint1());
            $this->assertInstanceOf(PropertyExistenceInterface::class, $res->getConstraint2());
        } elseif ($nbConstraints > 2) {
            $this->assertInstanceOf($expectedClass, $res->getConstraint1());
            $this->assertInstanceOf(PropertyExistenceInterface::class, $res->getConstraint2());
        }
    }

    public function provideDisaptchConstraintsLeaf()
    {
        return array(
            array(
                'ConstraintFieldIsset', array('alias_1.rop_1'),
                'PropertyExistenceInterface'
            ),
            array(
                'ConstraintFullTextSearch', array('alias_1.prop_1', 'search_expr'),
                'FullTextSearchInterface'
            ),
            array(
                'ConstraintSame', array('alias_1', '/path'),
                'SameNodeInterface'
            ),
            array(
                'ConstraintDescendant', array('alias_1', '/ancestor/path'),
                'DescendantNodeInterface'
            ),
            array(
                'ConstraintChild', array('alias_1', '/parent/path'),
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
        $constraint = $this->createNode($class, $args);
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
                ->field('alias_1.prop_1')
                ->literal('foobar');

        $res = $this->converter->dispatch($comparison);

        $this->assertInstanceOf(ComparisonInterface::class, $res);
        $this->assertInstanceOf(PropertyValueInterface::class, $res->getOperand1());
        $this->assertInstanceOf(LiteralInterface::class, $res->getOperand2());
        $this->assertEquals($expectedOperator, $res->getOperator());
    }

    public function testDispatchConstraintsNot()
    {
        $this->primeBuilder();
        $not = $this->qb->where()
            ->not()->fieldIsset('alias_1.prop_1');

        $res = $this->converter->dispatch($not);

        $this->assertInstanceOf(NotInterface::class, $res);
        $this->assertInstanceOf(PropertyExistenceInterface::class, $res->getConstraint());
    }

    public function provideTestDispatchOperands()
    {
        return array(
            // leaf
            array('OperandDynamicLocalName', array('alias_1'), array(
                'assert' => function ($test, $node) {
                    $test->assertEquals('alias_1', $node->getSelectorName());
                },
                'phpcr_class' => 'NodeLocalNameInterface',
            )),
            array('OperandDynamicName', array('alias_1'), array(
                'assert' => function ($test, $node) {
                    $test->assertEquals('alias_1', $node->getSelectorName());
                },
                'phpcr_class' => 'NodeNameInterface',
            )),
            array('OperandDynamicFullTextSearchScore', array('alias_1'), array(
                'assert' => function ($test, $node) {
                    $test->assertEquals('alias_1', $node->getSelectorName());
                },
                'phpcr_class' => 'FullTextSearchScoreInterface',
            )),
            array('OperandDynamicLength', array('alias_1.field'), array(
                'assert' => function ($test, $node) {
                    $propertyValue = $node->getPropertyValue();
                    $test->assertInstanceOf(
                        PropertyValueInterface::class,
                        $propertyValue
                    );
                    $test->assertEquals('alias_1', $propertyValue->getSelectorName());
                    $test->assertEquals('field_phpcr', $propertyValue->getPropertyName());
                },
                'phpcr_class' => 'LengthInterface',
            )),
            array('OperandDynamicField', array('alias_1.field'), array(
                'assert' => function ($test, $node) {
                    $test->assertEquals('alias_1', $node->getSelectorName());
                    $test->assertEquals('field_phpcr', $node->getPropertyName());
                },
                'phpcr_class' => 'PropertyValueinterface',
            )),

            // non-leaf
            array('OperandDynamicLowerCase', array('alias_1'), array(
                'phpcr_class' => 'LowerCaseInterface',
                'add_child_operand' => true,
                'assert' => function ($test, $node) {
                    $op = $node->getOperand();
                    $test->assertInstanceOf(
                        NodeLocalNameInterface::class,
                        $op
                   );
                }
            )),
            array('OperandDynamicUpperCase', array('alias_1'), array(
                'assert' => function ($test, $node) {
                    $op = $node->getOperand();
                    $test->assertInstanceOf(
                        NodeLocalNameInterface::class,
                        $op
                   );
                },
                'add_child_operand' => true,
                'phpcr_class' => 'UpperCaseInterface',
            )),

            // static
            array('OperandStaticParameter', array('variable_name'), array(
                'assert' => function ($test, $node) {
                    $test->assertEquals('variable_name', $node->getBindVariableName());
                },
                'phpcr_class' => 'BindVariableValueInterface',
            )),
            array('OperandStaticLiteral', array('literal_value'), array(
                'assert' => function ($test, $node) {
                    $test->assertEquals('literal_value', $node->getLiteralValue());
                },
                'phpcr_class' => 'LiteralInterface',
            )),
        );
    }

    /**
     * @dataProvider provideTestDispatchOperands
     */
    public function testDispatchOperands($class, $args, $options)
    {
        $options = array_merge(array(
            'assert' => null,
            'add_child_operand' => false,
            'phpcr_class' => null,
        ), $options);

        $expectedPhpcrClass = '\\PHPCR\\Query\\QOM\\'.$options['phpcr_class'];

        $this->primeBuilder();

        $operand = $this->createNode($class, $args);

        if ($options['add_child_operand']) {
            $operand->addChild(
                $this->createNode('OperandDynamicLocalName', array('alias_1'))
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
        $this->primeBuilder();
        $order1 = $this->createNode('Ordering', array(QOMConstants::JCR_ORDER_ASCENDING));
        $order2 = $this->createNode('Ordering', array(QOMConstants::JCR_ORDER_ASCENDING));
        $order3 = $this->createNode('Ordering', array(QOMConstants::JCR_ORDER_DESCENDING));

        $orderBy = $this->createNode('OrderBy', array());
        $orderBy->addChild($order1);
        $orderBy->addChild($order2);

        $op = $this->createNode('OperandDynamicLocalName', array('alias_1'));
        $order1->addChild($op);
        $order2->addChild($op);
        $order3->addChild($op);

        $orderByAdd = $this->createNode('OrderByAdd', array());
        $orderByAdd->addChild($order3);

        // original adds 2 orderings
        $res = $this->converter->dispatch($orderBy);
        $this->assertCount(2, $res);

        // orderByAdd adds 1 ordering, making 3
        $res = $this->converter->dispatch($orderByAdd);
        $this->assertCount(3, $res);

        // redispatching orderBy resets so we only have 2 again
        $res = $this->converter->dispatch($orderBy);
        $this->assertCount(2, $res);
    }

    public function provideOrderByDynamicField()
    {
        return array(
            array('alias_1.ok_field', null),
            array('alias_1.nodenameProperty', 'Cannot use nodename property "nodenameProperty" of class "MyClassName" as a dynamic operand use "localname()" instead.'),
            array('alias_1.associationfield', 'Cannot use association property "associationfield" of class "MyClassName" as a dynamic operand.')
        );
    }

    /**
     * @dataProvider provideOrderByDynamicField
     */
    public function testOrderByDynamicField($field, $exceptionMessage)
    {
        $this->primeBuilder();
        $order1 = $this->createNode('Ordering', array(QOMConstants::JCR_ORDER_ASCENDING));

        $orderBy = $this->createNode('OrderBy', array());
        $orderBy->addChild($order1);

        $op = $this->createNode('OperandDynamicField', array($field));
        $order1->addChild($op);

        if (null !== $exceptionMessage) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage($exceptionMessage);
        }

        $res = $this->converter->dispatch($orderBy);
        $this->assertCount(1, $res);
    }

    public function testGetQuery()
    {
        $me = $this;

        // setup the query, depends on the query builder
        // working properly..
        $this->qb->select()
            ->field('alias_1.foobar');

        $this->qb->from('alias_1')->document('Fooar', 'alias_1');
        $this->qb->where()->fieldIsset('alias_1.foobar');
        $this->qb->orderBy()->asc()->name('alias_1');

        // setup the qomf factory to expect the right parameters for createQuery
        $this->qomfFactory->expects($this->once())
            ->method('get')
            ->will($this->returnCallback(function ($class, $args) use ($me) {
                list($om, $source, $constraint, $orderings, $columns) = $args;
                $me->assertInstanceOf(
                    SourceInterface::class, $source
                );

                // test that we append the phpcr:class and classparents constraints
                $me->assertInstanceOf(
                    AndInterface::class, $constraint
                );
                $me->assertInstanceOf(
                    PropertyExistenceInterface::class, $constraint->getConstraint1()
                );
                $me->assertInstanceOf(
                    OrInterface::class, $constraint->getConstraint2()
                );
                $phpcrClassConstraint = $constraint->getConstraint2()->getConstraint1();
                $me->assertEquals(
                    'phpcr:class', $phpcrClassConstraint->getOperand1()->getPropertyName()
                );
                $me->assertEquals(
                    'Fooar', $phpcrClassConstraint->getOperand2()->getLiteralValue()
                );
                $phpcrClassParentsConstraint = $constraint->getConstraint2()->getConstraint2();
                $me->assertEquals(
                    'phpcr:classparents', $phpcrClassParentsConstraint->getOperand1()->getPropertyName()
                );
                $me->assertEquals(
                    'Fooar', $phpcrClassParentsConstraint->getOperand2()->getLiteralValue()
                );

                // test columns
                $me->assertCount(1, $columns);

                $column = $columns[0];
                $me->assertInstanceOf(
                    ColumnInterface::class, $column
                );

                // test orderings
                $me->assertCount(1, $orderings);
                $ordering = $orderings[0];
                $me->assertInstanceOf(
                    OrderingInterface::class, $ordering
                );

                // return something ..
                return $me->createMock(QueryObjectModelInterface::class);
            }));

        $phpcrQuery = $this->converter->getQuery($this->qb);

        $this->assertInstanceOf(Query::class, $phpcrQuery);
    }

    public function testGetQueryMoreThanOneSourceNoPrimaryAlias()
    {
        $this->qb->from()
            ->joinInner()
                ->left()->document('foobar', 'alias_1')->end()
                ->right()->document('barfoo', 'alias_2')->end()
                ->condition()->child('child_alias', 'parent_alias')->end();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You must specify a primary alias');
        $this->qb->getQuery();
    }
}
