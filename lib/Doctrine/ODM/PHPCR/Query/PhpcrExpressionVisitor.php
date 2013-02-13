<?php

namespace Doctrine\ODM\PHPCR\Query;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\ODM\PHPCR\Query\Expression\Descendant;
use Doctrine\ODM\PHPCR\Query\Expression\TextSearch;
use Doctrine\ODM\PHPCR\Query\Expression\Comparison as ODMComparison;

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstant;

/**
 * Class used by the QueryBuilder to convert Expression classes
 * to their PHPCR counterparts.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
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
            case ODMComparison::LIKE:
                $qomOperator = QOMConstant::JCR_OPERATOR_LIKE;
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
        $constraintList = array();

        foreach ($expr->getExpressionList() as $child) {
            $constraintList[] = $this->dispatch($child);
        }

        if (count($constraintList) < 2) {
            throw new \RuntimeException(sprintf(
                'Composite "%s" must have at least two constraints! (%d given)',
                $expr->getType(),
                count($constraintList)
            ));
        }

        switch ($expr->getType()) {
            case CompositeExpression::TYPE_AND:
                $method = 'andConstraint';
                break;
            case CompositeExpression::TYPE_OR:
                $method = 'orConstraint';
                break;
            default:
                throw new \RuntimeException("Unknown composite " . $expr->getType());
        }

        $firstConstraint = array_shift($constraintList);
        $firstComposite = null;

        foreach ($constraintList as $constraint) {
            $composite = $this->qomf->$method($firstConstraint, $constraint);

            $firstConstraint = $composite;
        }

        return $composite;
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
        return $this->qomf->literal($value->getValue());
    }

    /**
     * Convert a descendant expression to its PHPCR counterpart.
     *
     * @param \Doctrine\ODM\PHPCR\Query\Expression\Descendant
     *
     * @return \PHPCR\Query\QOM\DescendantNodeInterface
     */
    public function walkDescendant(Descendant $expr)
    {
        return $this->qomf->descendantNode($expr->getPath());
    }

    /**
     * Convert a testSearch expression to its PHPCR counterpart.
     *
     * @param \Doctrine\ODM\PHPCR\Query\Expression\TextSearch
     *
     * @return \PHPCR\Query\QOM\FullTextSearchInterface
     */
    public function walkTextSearch(TextSearch $expr)
    {
        return $this->qomf->fullTextSearch($expr->getField(), $expr->getSearch());
    }

    /**
     * Dispatch walking an expression to the appropriate handler.
     *
     * @param Expression
     *
     * @return mixed
     */
    public function dispatch(Expression $expr)
    {
        switch (true) {
            case ($expr instanceof Comparison):
                return $this->walkComparison($expr);

            case ($expr instanceof Value):
                return $this->walkValue($expr);

            case ($expr instanceof CompositeExpression):
                return $this->walkCompositeExpression($expr);

            case ($expr instanceof Descendant):
                return $this->walkDescendant($expr);

            case ($expr instanceof TextSearch):
                return $this->walkTextSearch($expr);

            default:
                throw new \RuntimeException("Unknown Expression " . get_class($expr));
        }
    }
}
