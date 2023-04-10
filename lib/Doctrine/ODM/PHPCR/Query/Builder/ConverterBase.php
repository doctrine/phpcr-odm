<?php

namespace Doctrine\ODM\PHPCR\Query\Builder;

use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode as QBConstants;
use PHPCR\Query\QOM\BindVariableValueInterface;
use PHPCR\Query\QOM\ChildNodeInterface;
use PHPCR\Query\QOM\ChildNodeJoinConditionInterface;
use PHPCR\Query\QOM\ColumnInterface;
use PHPCR\Query\QOM\ComparisonInterface;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\DescendantNodeInterface;
use PHPCR\Query\QOM\DescendantNodeJoinConditionInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;
use PHPCR\Query\QOM\EquiJoinConditionInterface;
use PHPCR\Query\QOM\FullTextSearchInterface;
use PHPCR\Query\QOM\FullTextSearchScoreInterface;
use PHPCR\Query\QOM\JoinConditionInterface;
use PHPCR\Query\QOM\JoinInterface;
use PHPCR\Query\QOM\LengthInterface;
use PHPCR\Query\QOM\LiteralInterface;
use PHPCR\Query\QOM\LowerCaseInterface;
use PHPCR\Query\QOM\NodeLocalNameInterface;
use PHPCR\Query\QOM\NodeNameInterface;
use PHPCR\Query\QOM\OrderingInterface;
use PHPCR\Query\QOM\PropertyExistenceInterface;
use PHPCR\Query\QOM\PropertyValueInterface;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\SameNodeInterface;
use PHPCR\Query\QOM\SelectorInterface;
use PHPCR\Query\QOM\SourceInterface;
use PHPCR\Query\QOM\StaticOperandInterface;
use PHPCR\Query\QOM\UpperCaseInterface;

/**
 * Base class for PHPCR based query converters.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
abstract class ConverterBase implements ConverterInterface
{
    protected ?SourceInterface $from = null;

    /**
     * @var ColumnInterface[]
     */
    protected array $columns = [];

    /**
     * @var OrderingInterface[]
     */
    protected array $orderings = [];

    protected ?ConstraintInterface $constraint = null;

    /**
     * Return the PHPCR property name and alias for the given ODM document
     * property name and query alias.
     *
     * The alias might change if this is a translated field and the strategy
     * needs to do a join to get in the translation.
     *
     * @param string $originalAlias as specified in the query source
     * @param string $odmField      name of ODM document property
     *
     * @return string[] first element is the real alias to use, second element is
     *                  the property name
     *
     * @throws \Exception if a field used in the query does not exist on the document
     */
    abstract protected function getPhpcrProperty(string $originalAlias, string $odmField): array;

    abstract protected function walkSourceDocument(SourceDocument $node): SelectorInterface;

    /**
     * Implementations should map their domain field name to the PHPCR field name here.
     */
    abstract protected function walkOperandDynamicField(OperandDynamicField $node): PropertyValueInterface;

    /**
     * Return the query object model factory.
     */
    abstract protected function qomf(): QueryObjectModelFactoryInterface;

    /**
     * Check that the given alias is valid and return it.
     *
     * This should only be called from the getQuery function AFTER
     * the document sources are known.
     *
     * @return string Return the alias to allow this method to be chained
     *
     * @throws InvalidArgumentException
     */
    abstract protected function validateAlias(string $alias): string;

    /**
     * Convenience method to dispatch an array of nodes.
     *
     * @param AbstractNode[] $nodes
     */
    protected function dispatchMany(array $nodes): void
    {
        foreach ($nodes as $node) {
            $this->dispatch($node);
        }
    }

    /**
     * Dispatch a node.
     *
     * This method will look for a method of the form
     * "walk{NodeType}" in this class and then use that
     * to build the PHPCR QOM counterpart of the given node.
     *
     * @return object|array - PHPCR QOM object or array of objects
     */
    public function dispatch(AbstractNode $node)
    {
        $methodName = sprintf('walk%s', $node->getName());

        if (!method_exists($this, $methodName)) {
            throw new InvalidArgumentException(sprintf(
                'Do not know how to walk node of type "%s"',
                $node->getName()
            ));
        }

        return $this->$methodName($node);
    }

    /**
     * @return ColumnInterface[]
     */
    public function walkSelect(AbstractNode $node): array
    {
        $columns = [];

        foreach ($node->getChildren() as $property) {
            \assert($property instanceof Field);
            [$alias, $phpcrName] = $this->getPhpcrProperty(
                $property->getAlias(),
                $property->getField()
            );

            $column = $this->qomf()->column(
                $alias,
                $phpcrName,
                // do we want to support custom column names in ODM?
                $phpcrName
            );

            $columns[] = $column;
        }

        $this->columns = $columns;

        return $this->columns;
    }

    /**
     * @return ColumnInterface[]
     */
    public function walkSelectAdd(SelectAdd $node): array
    {
        $columns = $this->columns;
        $addColumns = $this->walkSelect($node);
        $this->columns = array_merge(
            $columns,
            $addColumns
        );

        return $this->columns;
    }

    public function walkFrom(AbstractNode $node): SourceInterface
    {
        $source = $node->getChild();
        $res = $this->dispatch($source);
        \assert($res instanceof SourceInterface);

        $this->from = $res;

        return $this->from;
    }

    public function walkWhere(Where $where): ConstraintInterface
    {
        $constraint = $where->getChild();
        $res = $this->dispatch($constraint);
        \assert($res instanceof ConstraintInterface);
        $this->constraint = $res;

        return $this->constraint;
    }

    public function walkWhereAnd(WhereAnd $whereAnd): ConstraintInterface
    {
        if (!$this->constraint) {
            return $this->walkWhere($whereAnd);
        }

        $constraint = $whereAnd->getChild();
        $res = $this->dispatch($constraint);
        \assert($res instanceof ConstraintInterface);
        $newConstraint = $this->qomf()->andConstraint(
            $this->constraint,
            $res
        );
        $this->constraint = $newConstraint;

        return $this->constraint;
    }

    public function walkWhereOr(WhereOr $whereOr): ConstraintInterface
    {
        if (!$this->constraint) {
            return $this->walkWhere($whereOr);
        }

        $constraint = $whereOr->getChild();
        $res = $this->dispatch($constraint);
        \assert($res instanceof ConstraintInterface);
        $newConstraint = $this->qomf()->orConstraint(
            $this->constraint,
            $res
        );
        $this->constraint = $newConstraint;

        return $this->constraint;
    }

    protected function walkSourceJoin(SourceJoin $node): JoinInterface
    {
        $left = $this->dispatch($node->getChildOfType(QBConstants::NT_SOURCE_JOIN_LEFT));
        \assert($left instanceof SourceInterface);
        $right = $this->dispatch($node->getChildOfType(QBConstants::NT_SOURCE_JOIN_RIGHT));
        \assert($right instanceof SourceInterface);
        $cond = $this->dispatch($node->getChildOfType(QBConstants::NT_SOURCE_JOIN_CONDITION_FACTORY));
        \assert($cond instanceof JoinConditionInterface);

        return $this->qomf()->join($left, $right, $node->getJoinType(), $cond);
    }

    protected function walkSourceJoinLeft(SourceJoinLeft $node): SourceInterface
    {
        return $this->walkFrom($node);
    }

    protected function walkSourceJoinRight(SourceJoinRight $node): SourceInterface
    {
        return $this->walkFrom($node);
    }

    protected function walkSourceJoinConditionFactory(SourceJoinConditionFactory $node)
    {
        return $this->dispatch($node->getChild());
    }

    protected function walkSourceJoinConditionEqui(SourceJoinConditionEqui $node): EquiJoinConditionInterface
    {
        [$alias1, $phpcrProperty1] = $this->getPhpcrProperty(
            $node->getAlias1(),
            $node->getProperty1()
        );
        [$alias2, $phpcrProperty2] = $this->getPhpcrProperty(
            $node->getAlias2(),
            $node->getProperty2()
        );

        return $this->qomf()->equiJoinCondition(
            $alias1,
            $phpcrProperty1,
            $alias2,
            $phpcrProperty2
        );
    }

    protected function walkSourceJoinConditionDescendant(SourceJoinConditionDescendant $node): DescendantNodeJoinConditionInterface
    {
        return $this->qomf()->descendantNodeJoinCondition(
            $node->getDescendantAlias(),
            $node->getAncestorAlias()
        );
    }

    protected function walkSourceJoinConditionChildDocument(SourceJoinConditionChildDocument $node): ChildNodeJoinConditionInterface
    {
        return $this->qomf()->childNodeJoinCondition(
            $node->getChildAlias(),
            $node->getParentAlias()
        );
    }

    protected function walkSourceJoinConditionSameDocument(SourceJoinConditionSameDocument $node): ChildNodeJoinConditionInterface
    {
        return $this->qomf()->childNodeJoinCondition(
            $this->validateAlias($node->getAlias1Name()),
            $this->validateAlias($node->getAlias2Name()),
        );
    }

    protected function doWalkConstraintComposite(AbstractNode $node, $method)
    {
        $children = $node->getChildren();

        if (0 === count($children)) {
            throw new \InvalidArgumentException('Composite must have at least one constraint');
        }
        if (1 === count($children)) {
            return $this->dispatch(current($children));
        }

        $lConstraint = array_shift($children);
        $lPhpcrConstraint = $this->dispatch($lConstraint);
        $phpcrComposite = false;

        foreach ($children as $rConstraint) {
            $rPhpcrConstraint = $this->dispatch($rConstraint);
            $phpcrComposite = $this->qomf()->$method($lPhpcrConstraint, $rPhpcrConstraint);

            $lPhpcrConstraint = $phpcrComposite;
        }

        return $phpcrComposite;
    }

    protected function walkConstraintAndX(ConstraintAndx $node)
    {
        return $this->doWalkConstraintComposite($node, 'andConstraint');
    }

    protected function walkConstraintOrX(ConstraintOrx $node)
    {
        return $this->doWalkConstraintComposite($node, 'orConstraint');
    }

    protected function walkConstraintFieldIsset(ConstraintFieldIsset $node): PropertyExistenceInterface
    {
        [$alias, $phpcrProperty] = $this->getPhpcrProperty(
            $node->getAlias(),
            $node->getField()
        );

        return $this->qomf()->propertyExistence(
            $alias,
            $phpcrProperty
        );
    }

    protected function walkConstraintFullTextSearch(ConstraintFullTextSearch $node): FullTextSearchInterface
    {
        [$alias, $phpcrProperty] = $this->getPhpcrProperty(
            $node->getAlias(),
            $node->getField()
        );

        return $this->qomf()->fullTextSearch(
            $alias,
            $phpcrProperty,
            $node->getFullTextSearchExpression()
        );
    }

    protected function walkConstraintSame(ConstraintSame $node): SameNodeInterface
    {
        return $this->qomf()->sameNode(
            $this->validateAlias($node->getAlias()),
            $node->getPath()
        );
    }

    protected function walkConstraintDescendant(ConstraintDescendant $node): DescendantNodeInterface
    {
        return $this->qomf()->descendantNode(
            $this->validateAlias($node->getAlias()),
            $node->getAncestorPath()
        );
    }

    protected function walkConstraintChild(ConstraintChild $node): ChildNodeInterface
    {
        return $this->qomf()->childNode(
            $this->validateAlias($node->getAlias()),
            $node->getParentPath()
        );
    }

    protected function walkConstraintComparison(ConstraintComparison $node): ComparisonInterface
    {
        $dynOp = $node->getChildOfType(
            QBConstants::NT_OPERAND_DYNAMIC
        );
        $statOp = $node->getChildOfType(
            QBConstants::NT_OPERAND_STATIC
        );

        $phpcrDynOp = $this->dispatch($dynOp);
        \assert($phpcrDynOp instanceof DynamicOperandInterface);
        $phpcrStatOp = $this->dispatch($statOp);
        \assert($phpcrStatOp instanceof StaticOperandInterface);

        return $this->qomf()->comparison(
            $phpcrDynOp,
            $node->getOperator(),
            $phpcrStatOp
        );
    }

    protected function walkConstraintNot(ConstraintNot $node): ConstraintInterface
    {
        $con = $node->getChildOfType(
            QBConstants::NT_CONSTRAINT
        );

        $phpcrCon = $this->dispatch($con);
        \assert($phpcrCon instanceof ConstraintInterface);

        return $this->qomf()->notConstraint(
            $phpcrCon
        );
    }

    protected function walkOperandDynamicLocalName(OperandDynamicLocalName $node): NodeLocalNameInterface
    {
        return $this->qomf()->nodeLocalName(
            $this->validateAlias($node->getAlias())
        );
    }

    protected function walkOperandDynamicFullTextSearchScore(OperandDynamicFullTextSearchScore $node): FullTextSearchScoreInterface
    {
        return $this->qomf()->fullTextSearchScore(
            $this->validateAlias($node->getAlias())
        );
    }

    protected function walkOperandDynamicLength(OperandDynamicLength $node): LengthInterface
    {
        [$alias, $phpcrProperty] = $this->getPhpcrProperty(
            $node->getAlias(),
            $node->getField()
        );

        $propertyValue = $this->qomf()->propertyValue(
            $alias,
            $phpcrProperty
        );

        return $this->qomf()->length(
            $propertyValue
        );
    }

    protected function walkOperandDynamicName(OperandDynamicName $node): NodeNameInterface
    {
        return $this->qomf()->nodeName(
            $this->validateAlias($node->getAlias())
        );
    }

    protected function walkOperandDynamicLowerCase(OperandDynamicLowerCase $node): LowerCaseInterface
    {
        $child = $node->getChildOfType(
            QBConstants::NT_OPERAND_DYNAMIC
        );

        $phpcrChild = $this->dispatch($child);
        \assert($phpcrChild instanceof DynamicOperandInterface);

        return $this->qomf()->lowerCase(
            $phpcrChild
        );
    }

    protected function walkOperandDynamicUpperCase(OperandDynamicUpperCase $node): UpperCaseInterface
    {
        $child = $node->getChildOfType(
            QBConstants::NT_OPERAND_DYNAMIC
        );

        $phpcrChild = $this->dispatch($child);
        \assert($phpcrChild instanceof DynamicOperandInterface);

        return $this->qomf()->upperCase(
            $phpcrChild
        );
    }

    protected function walkOperandStaticLiteral(OperandStaticLiteral $node): LiteralInterface
    {
        return $this->qomf()->literal($node->getValue());
    }

    protected function walkOperandStaticParameter(OperandStaticParameter $node): BindVariableValueInterface
    {
        return $this->qomf()->bindVariable($node->getVariableName());
    }

    // ordering

    /**
     * @return OrderingInterface[]
     */
    protected function walkOrderBy(OrderBy $node): array
    {
        $this->orderings = [];

        $orderings = $node->getChildren();

        foreach ($orderings as $ordering) {
            \assert($ordering instanceof Ordering);
            $dynOp = $ordering->getChildOfType(
                QBConstants::NT_OPERAND_DYNAMIC
            );

            $phpcrDynOp = $this->dispatch($dynOp);
            \assert($phpcrDynOp instanceof DynamicOperandInterface);

            if (QOMConstants::JCR_ORDER_ASCENDING === $ordering->getOrder()) {
                $ordering = $this->qomf()->ascending($phpcrDynOp);
            } else {
                $ordering = $this->qomf()->descending($phpcrDynOp);
            }

            $this->orderings[] = $ordering;
        }

        return $this->orderings;
    }

    /**
     * @return OrderingInterface[]
     */
    protected function walkOrderByAdd(OrderBy $node): array
    {
        $this->orderings = array_merge(
            $this->orderings,
            $this->walkOrderBy($node)
        );

        return $this->orderings;
    }
}
