<?php

namespace Doctrine\ODM\PHPCR\Query;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Common\Collections\Expr\CompositeExpression;

use PHPCR\Query\QOM\DynamicOperandInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\StaticOperandInterface; 
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstant;

class PhpcrExpressionVisitor extends ExpressionVisitor
{
    protected $qomf;

    public function __construct(QueryObjectModelFactoryInterface $qomf)
    {
        $this->qomf = $qomf;
    }

    /**
     * Convert a comparison expression into the target query language output
     *
     * @param \Doctrine\Common\Collections\Expr\Comparison $comparison
     *
     * @return mixed
     */
    public function walkComparison(Comparison $comparison)
    {
        $field = $comparison->getField();
        $value = $comparison->getValue()->getValue(); // shortcut for walkValue()
        $operator = $comparison->getOperator();

        switch ($operator) {
            case Comparison::EQ:
                $qomOperator = QOMConstant::JCR_OPERATOR_EQUAL_TO;
                break;
            case Comparison::NEQ:
                $qomOperator = QOMConstant::JCR_OPERATOR_NOT_EQUAL_TO;
                break;
            case Comparison::LT:
                $qomOperator = QOMConstant::JCR_OPERATOR_LESS_THAN;
                break;
            case Comparison::LTE:
                $qomOperator = QOMConstant::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO;
                break;
            case Comparison::GTE:
                $qomOperator = QOMConstant::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO;
                break;
            case Comparison::GT:
                $qomOperator = QOMConstant::JCR_OPERATOR_GREATER_THAN;
                break;
            default:
                throw new \InvalidArgumentException("Unsupported operator $operator");
        }

        $qomField = $this->qomf->propertyValue($field);
        $qomValue = $this->qomf->literal($value);

        return $this->qomf->comparison($qomField, $qomOperator, $qomValue);
    }

    /**
     * Convert a composite expression into the target query language output
     *
     * @param \Doctrine\Common\Collections\Expr\CompositeExpression $expr
     *
     * @return mixed
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = array();

        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }

        switch($expr->getType()) {
            case CompositeExpression::TYPE_AND:
                return '(' . implode(' AND ', $expressionList) . ')';

            case CompositeExpression::TYPE_OR:
                return '(' . implode(' OR ', $expressionList) . ')';

            default:
                throw new \RuntimeException("Unknown composite " . $expr->getType());
        }
    }

    /**
     * Convert a value expression into the target query language part.
     *
     * @param \Doctrine\Common\Collections\Expr\Value $value
     *
     * @return mixed
     */
    public function walkValue(Value $value)
    {
        return '?';
    }
}
