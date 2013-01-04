<?php

namespace Doctrine\Tests\ODM\PHPCR\Query;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ODM\PHPCR\Query\PhpcrExpressionVisitor;
use PHPCR\Query\QOM\ComparisonConstraintInterface;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstant;

class PhpcrExpressionVisitorTest extends PHPCRFunctionalTestCase
{
    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $qm = $this->dm->getPhpcrSession()->getWorkspace()->getQueryManager();
        $this->qomf = $qm->getQOMFactory();

        $this->expr = new ExpressionBuilder;
        $this->visitor = new PhpcrExpressionVisitor($this->qomf);
    }

    public function getExpressions()
    {
        return array(
            array('foobar', 'eq', 123, QOMConstant::JCR_OPERATOR_EQUAL_TO),
            array('foobar', 'neq', 123, QOMConstant::JCR_OPERATOR_NOT_EQUAL_TO),
            array('foobar', 'lt', 123, QOMConstant::JCR_OPERATOR_LESS_THAN),
            array('foobar', 'lte', 123, QOMConstant::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO),
            array('foobar', 'gte', 123, QOMConstant::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO),
            array('foobar', 'gt', 123, QOMConstant::JCR_OPERATOR_GREATER_THAN),
        );
    }

    /**
     * @dataProvider getExpressions
     */
    public function testWalkComparison($field, $exprMethod, $value, $expectedJcrOperator)
    {
        $expr = $this->expr->$exprMethod($field, $value);
        $res = $this->visitor->walkComparison($expr);
        $this->assertInstanceOf('PHPCR\Query\QOM\ComparisonInterface', $res);

        $operand1 = $res->getOperand1();
        $operator = $res->getOperator();
        $operand2 = $res->getOperand2();

        $this->assertInstanceOf('PHPCR\Query\QOM\PropertyValueInterface', $operand1);
        $this->assertEquals($field, $operand1->getPropertyName());

        $this->assertEquals($expectedJcrOperator, $operator);

        $this->assertInstanceOf('PHPCR\Query\QOM\LiteralInterface', $operand2);
        $this->assertEquals($value, $operand2->getLiteralValue());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWalkComparison_unknownOperator()
    {
        $expr = $this->expr->in('asd', array('asd'));
        $this->visitor->walkComparison($expr);
    }

    public function testWalkCompositeExpression_two()
    {
        $expr = $this->expr->andx(
            $this->expr->eq('foo', 'bar'),
            $this->expr->eq('bar', 'foo')
        );

        $res = $this->visitor->walkCompositeExpression($expr);
        $this->assertInstanceOf('PHPCR\Query\QOM\AndInterface', $res);
        $this->assertInstanceOf('PHPCR\Query\QOM\ComparisonInterface', $res->getConstraint1());
        $this->assertEquals(QOMConstant::JCR_OPERATOR_EQUAL_TO, $res->getConstraint1()->getOperator());
    }

    public function testWalkCompositeExpression_three()
    {
        // Should be equivilent to
        // ("foo" == "bar" AND bar = "foo") AND "bar" > $foo

        // NOTE: I wonder if we should just now allow composites of more than two
        $expr = $this->expr->andx(
            $this->expr->eq('foo', 'bar'),
            $this->expr->eq('bar', 'foo'),
            $this->expr->gt('bar', 'foo')
        );

        $res = $this->visitor->walkCompositeExpression($expr);
        $this->assertInstanceOf('PHPCR\Query\QOM\AndInterface', $res);
        $this->assertInstanceOf('PHPCR\Query\QOM\AndInterface', $res->getConstraint1());
        $this->assertInstanceOf('PHPCR\Query\QOM\ComparisonInterface', $res->getConstraint2());
    }

    public function testWalkValue()
    {
        $val = new \Doctrine\Common\Collections\Expr\Value('test');
        $res = $this->visitor->walkValue($val);
        $this->assertInstanceOf('PHPCR\Query\QOM\LiteralInterface', $res);
        $this->assertEquals('test', $res->getLiteralValue());
    }
}

