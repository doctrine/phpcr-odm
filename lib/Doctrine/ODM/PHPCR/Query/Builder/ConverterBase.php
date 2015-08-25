<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\PHPCR\Query\Builder;

use PHPCR\Query\QOM\OrderingInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface as QOMConstants;
use Doctrine\ODM\PHPCR\Query\Query;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode as QBConstants;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\ColumnInterface;
use PHPCR\Query\QOM\SourceInterface;

/**
 * Base class for PHPCR based query converters
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
abstract class ConverterBase implements ConverterInterface
{
    /**
     * @var SourceInterface
     */
    protected $from = null;

    /**
     * @var ColumnInterface[]
     */
    protected $columns = array();

    /**
     * @var OrderingInterface[]
     */
    protected $orderings = array();

    /**
     * @var ConstraintInterface
     */
    protected $constraint = null;

    /**
     * Return the PHPCR property name and alias for the given ODM document
     * property name and query alias.
     *
     * The alias might change if this is a translated field and the strategy
     * needs to do a join to get in the translation.
     *
     * @param string $originalAlias As specified in the query source.
     * @param string $odmField      Name of ODM document property.
     *
     * @return array first element is the real alias to use, second element is
     *      the property name
     *
     * @throws \Exception If a field used in the query does not exist on the document.
     */
    abstract protected function getPhpcrProperty($originalAlias, $odmField);

    /**
     * Walk the source document
     *
     * @param SourceDocument
     */
    abstract protected function walkSourceDocument(SourceDocument $node);

    /**
     * Walk the dynamic field
     *
     * Implementations should map their domain field name to the PHPCR field name here.
     *
     * @param OperandDynamicField $node
     */
    abstract protected function walkOperandDynamicField(OperandDynamicField $node);

    /**
     * Return the query object model factory
     *
     * @return QueryObjectModelFactoryInterface
     */
    abstract protected function qomf();

    /**
     * Check that the given alias is valid and return it.
     *
     * This should only be called from the getQuery function AFTER
     * the document sources are known.
     *
     * @param string $alias Alias to validate and return
     *
     * @return string Return the alias to allow this function to be used inline
     *
     * @throws InvalidArgumentException
     */
    abstract protected function validateAlias($alias);

    /**
     * Convenience method to dispatch an array of nodes.
     *
     * @param array
     */
    protected function dispatchMany($nodes)
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
     * @param AbstractNode $node
     *
     * @return object - PHPCR QOM object
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

        $res = $this->$methodName($node);

        return $res;
    }

    public function walkSelect(AbstractNode $node)
    {
        $columns = array();

        /** @var $property Field */
        foreach ($node->getChildren() as $property) {
            list($alias, $phpcrName) = $this->getPhpcrProperty(
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

    public function walkSelectAdd(SelectAdd $node)
    {
        $columns = $this->columns;
        $addColumns = $this->walkSelect($node);
        $this->columns = array_merge(
            $columns,
            $addColumns
        );

        return $this->columns;
    }

    public function walkFrom(AbstractNode $node)
    {
        $source = $node->getChild();
        $res = $this->dispatch($source);

        $this->from = $res;

        return $this->from;
    }

    public function walkWhere(Where $where)
    {
        $constraint = $where->getChild();
        $res = $this->dispatch($constraint);
        $this->constraint = $res;

        return $this->constraint;
    }

    public function walkWhereAnd(WhereAnd $whereAnd)
    {
        if (!$this->constraint) {
            return $this->walkWhere($whereAnd);
        }

        $constraint = $whereAnd->getChild();
        $res = $this->dispatch($constraint);
        $newConstraint = $this->qomf()->andConstraint(
            $this->constraint,
            $res
        );
        $this->constraint = $newConstraint;

        return $this->constraint;
    }

    public function walkWhereOr(WhereOr $whereOr)
    {
        if (!$this->constraint) {
            return $this->walkWhere($whereOr);
        }

        $constraint = $whereOr->getChild();
        $res = $this->dispatch($constraint);
        $newConstraint = $this->qomf()->orConstraint(
            $this->constraint,
            $res
        );
        $this->constraint = $newConstraint;

        return $this->constraint;
    }

    protected function walkSourceJoin(SourceJoin $node)
    {
        $left = $this->dispatch($node->getChildOfType(QBConstants::NT_SOURCE_JOIN_LEFT));
        $right = $this->dispatch($node->getChildOfType(QBConstants::NT_SOURCE_JOIN_RIGHT));
        $cond = $this->dispatch($node->getChildOfType(QBConstants::NT_SOURCE_JOIN_CONDITION_FACTORY));

        $join = $this->qomf()->join($left, $right, $node->getJoinType(), $cond);

        return $join;
    }

    protected function walkSourceJoinLeft(SourceJoinLeft $node)
    {
        $left = $this->walkFrom($node);
        return $left;
    }

    protected function walkSourceJoinRight(SourceJoinRight $node)
    {
        $right = $this->walkFrom($node);
        return $right;
    }

    protected function walkSourceJoinConditionFactory(SourceJoinConditionFactory $node)
    {
        $res = $this->dispatch($node->getChild());

        return $res;
    }

    protected function walkSourceJoinConditionEqui(SourceJoinConditionEqui $node)
    {
        list($alias1, $phpcrProperty1) = $this->getPhpcrProperty(
            $node->getAlias1(), $node->getProperty1()
        );
        list($alias2, $phpcrProperty2) = $this->getPhpcrProperty(
            $node->getAlias2(), $node->getProperty2()
        );

        $equi = $this->qomf()->equiJoinCondition(
            $alias1, $phpcrProperty1,
            $alias2, $phpcrProperty2
        );

        return $equi;
    }

    protected function walkSourceJoinConditionDescendant(SourceJoinConditionDescendant $node)
    {
        $joinCon = $this->qomf()->descendantNodeJoinCondition(
            $node->getDescendantAlias(),
            $node->getAncestorAlias()
        );
        return $joinCon;
    }

    protected function walkSourceJoinConditionChildDocument(SourceJoinConditionChildDocument $node)
    {
        $joinCon = $this->qomf()->childNodeJoinCondition(
            $node->getChildAlias(),
            $node->getParentAlias()
        );

        return $joinCon;
    }

    protected function walkSourceJoinConditionSameDocument(SourceJoinConditionSameDocument $node)
    {
        $joinCon = $this->qomf()->childNodeJoinCondition(
            $this->validateAlias($node->getAlias1Name()),
            $this->validateAlias($node->getAlias2Name()),
            $node->getAlias2Path()
        );
        return $joinCon;
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

    protected function walkConstraintAndX(ConstraintAndX $node)
    {
        return $this->doWalkConstraintComposite($node, 'andConstraint');
    }

    protected function walkConstraintOrX(ConstraintOrX $node)
    {
        return $this->doWalkConstraintComposite($node, 'orConstraint');
    }

    protected function walkConstraintFieldIsset(ConstraintFieldIsset $node)
    {
        list($alias, $phpcrProperty) = $this->getPhpcrProperty(
            $node->getAlias(), $node->getField()
        );

        $con = $this->qomf()->propertyExistence(
            $alias,
            $phpcrProperty
        );

        return $con;
    }

    protected function walkConstraintFullTextSearch(ConstraintFullTextSearch $node)
    {
        list($alias, $phpcrProperty) = $this->getPhpcrProperty(
            $node->getAlias(), $node->getField()
        );

        $con = $this->qomf()->fullTextSearch(
            $alias,
            $phpcrProperty,
            $node->getFullTextSearchExpression()
        );

        return $con;
    }

    protected function walkConstraintSame(ConstraintSame $node)
    {
        $con = $this->qomf()->sameNode(
            $this->validateAlias($node->getAlias()),
            $node->getPath()
        );

        return $con;
    }

    protected function walkConstraintDescendant(ConstraintDescendant $node)
    {
        $con = $this->qomf()->descendantNode(
            $this->validateAlias($node->getAlias()),
            $node->getAncestorPath()
        );

        return $con;
    }

    protected function walkConstraintChild(ConstraintChild $node)
    {
        $con = $this->qomf()->childNode(
            $this->validateAlias($node->getAlias()),
            $node->getParentPath()
        );

        return $con;
    }

    protected function walkConstraintComparison(ConstraintComparison $node)
    {
        $dynOp = $node->getChildOfType(
            QBConstants::NT_OPERAND_DYNAMIC
        );
        $statOp = $node->getChildOfType(
            QBConstants::NT_OPERAND_STATIC
        );

        $phpcrDynOp = $this->dispatch($dynOp);
        $phpcrStatOp = $this->dispatch($statOp);

        $compa = $this->qomf()->comparison(
            $phpcrDynOp, $node->getOperator(), $phpcrStatOp
        );

        return $compa;
    }

    protected function walkConstraintNot(ConstraintNot $node)
    {
        $con = $node->getChildOfType(
            QBConstants::NT_CONSTRAINT
        );

        $phpcrCon = $this->dispatch($con);

        $ret = $this->qomf()->notConstraint(
            $phpcrCon
        );

        return $ret;
    }

    protected function walkOperandDynamicLocalName(OperandDynamicLocalName $node)
    {
        $operand = $this->qomf()->nodeLocalName(
            $this->validateAlias($node->getAlias())
        );

        return $operand;
    }

    protected function walkOperandDynamicFullTextSearchScore(OperandDynamicFullTextSearchScore $node)
    {
        $operand = $this->qomf()->fullTextSearchScore(
            $this->validateAlias($node->getAlias())
        );

        return $operand;
    }

    protected function walkOperandDynamicLength(OperandDynamicLength $node)
    {
        list($alias, $phpcrProperty) = $this->getPhpcrProperty(
            $node->getAlias(),
            $node->getField()
        );

        $propertyValue = $this->qomf()->propertyValue(
            $alias,
            $phpcrProperty
        );

        $operand = $this->qomf()->length(
            $propertyValue
        );

        return $operand;
    }

    protected function walkOperandDynamicName(OperandDynamicName $node)
    {
        $operand = $this->qomf()->nodeName(
            $this->validateAlias($node->getAlias())
        );

        return $operand;
    }

    protected function walkOperandDynamicLowerCase(OperandDynamicLowerCase $node)
    {
        $child = $node->getChildOfType(
            QBConstants::NT_OPERAND_DYNAMIC
        );

        $phpcrChild = $this->dispatch($child);

        $operand = $this->qomf()->lowerCase(
            $phpcrChild
        );

        return $operand;
    }

    protected function walkOperandDynamicUpperCase(OperandDynamicUpperCase $node)
    {
        $child = $node->getChildOfType(
            QBConstants::NT_OPERAND_DYNAMIC
        );

        $phpcrChild = $this->dispatch($child);

        $operand = $this->qomf()->upperCase(
            $phpcrChild
        );

        return $operand;
    }

    protected function walkOperandStaticLiteral(OperandStaticLiteral $node)
    {
        $operand = $this->qomf()->literal($node->getValue());
        return $operand;
    }

    protected function walkOperandStaticParameter(OperandStaticParameter $node)
    {
        $operand = $this->qomf()->bindVariable($node->getVariableName());
        return $operand;
    }

    // ordering
    protected function walkOrderBy(OrderBy $node)
    {
        $this->orderings = array();

        $orderings = $node->getChildren();

        /** @var $ordering Ordering */
        foreach ($orderings as $ordering) {
            $dynOp = $ordering->getChildOfType(
                QBConstants::NT_OPERAND_DYNAMIC
            );

            $phpcrDynOp = $this->dispatch($dynOp);

            if ($ordering->getOrder() == QOMConstants::JCR_ORDER_ASCENDING) {
                $ordering = $this->qomf()->ascending($phpcrDynOp);
            } else {
                $ordering = $this->qomf()->descending($phpcrDynOp);
            }

            $this->orderings[] = $ordering;
        }

        return $this->orderings;
    }

    protected function walkOrderByAdd(OrderBy $node)
    {
        $this->orderings = array_merge(
            $this->orderings,
            $this->walkOrderBy($node)
        );

        return $this->orderings;
    }
}
