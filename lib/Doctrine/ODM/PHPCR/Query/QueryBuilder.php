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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\PHPCR\Query;

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;

/**
 * QueryBuilder clas ir responsible for dynamically create QOM queries.
 *
 * TODO: bind parameters
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @author      Nacho Martín <nitram.ohcan@gmail.com>
 */
class QueryBuilder
{
    /** The builder states. */
    const STATE_DIRTY = 0;
    const STATE_CLEAN = 1;

    /**
     * @var integer The state of the query object. Can be dirty or clean.
     */
    private $state = self::STATE_CLEAN;

    /**
     * @var PHPCR\Query\QOM\QueryObjectModelFactoryInterface QOMFactory
     */
    private $qomFactory;

    /**
     * @var integer The maximum number of results to retrieve.
     */
    private $firstResult = null;

    /**
     * @var integer The maximum number of results to retrieve.
     */
    private $maxResults = null;

    /**
     * @var array with the orderings that determine the order of the result
     */
    private $orderings = array();

    /**
     * @var PHPCR\Query\QOM\ConstraintInterface to apply to the query.
     */
    private $constraint = null;

    /**
     * @var array with the columns to be selected.
     */
    private $columns = array();

    /**
     * @var PHPCR\Query\QOM\SourceInterface source of the query.
     */
    private $source = null;

    /**
     * @var PHPCR\Query\QueryObjectModelInterface
     */
    private $query = null;

    /**
     * Initializes a new QueryBuilder
     *
     * @param PHPCR\Query\QOM\QueryObjectModelFactoryInterface $qomFactory
     */
    public function __construct(QueryObjectModelFactoryInterface $qomFactory)
    {
        $this->qomFactory = $qomFactory;
    }

    /**
     * Get the associated QOMFactory for this query builder
     *
     * @return PHPCR\Query\QOM\QueryObjectModelFactoryInterface
     */
    public function getQOMFactory()
    {
        return $this->qomFactory;
    }

    /**
     * sets the position of the first result to retrieve (the "offset").
     *
     * @param integer $firstResult The First result to return.
     * @return Doctrine\ODM\PHPCR\Query\QueryBuilder This QueryBuilder instance.
     */
    public function setFirstResult($firstResult)
    {
        $this->firstResult = $firstResult;
        return $this;
    }

    /**
     * getFirstResult
     * Gets the position of the first result the query object was set to retrieve (the "offset").
     * Returns NULL if {@link setFirstResult} was not applied to this QueryBuilder.
     *
     * @return integer The position of the first result.
     */
    public function getFirstResult()
    {
        return $this->firstResult;
    }

    /**
     *
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param integer $maxResults The maximum number of results to retrieve.
     * @return Doctrine\ODM\PHPCR\Query\QueryBuilder This QueryBuilder instance.
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
        return $this;
    }

    /**
     * Gets the maximum number of results the query object was set to retrieve (the "limit").
     * Returns NULL if {@link setMaxResults} was not applied to this query builder.
     *
     * @return integer Maximum number of results.
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }


    /**
     * Gets the array of orderings.
     *
     * @return arrray Orderings to apply.
     */
    public function getOrderings()
    {
        return $this->orderings;
    }

    /**
     * Adds an ordering to the query results.
     *
     * @param PHPCR\Query\QOM\DynamicOperandInterface $sort The ordering expression.
     * @param string $order The ordering direction.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function addOrderBy(\PHPCR\Query\QOM\DynamicOperandInterface $sort, $order = null)
    {
        $this->state = self::STATE_DIRTY;
        if ($order == 'ASC' ) {
            $ordering = $this->qomFactory->ascending($sort);
        } else {
            $ordering = $this->qomFactory->descending($sort);
        }
        $this->orderings[] = $ordering;
        return $this;
    }

    /**
     * Specifies an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * @param PHPCR\Query\QOM\DynamicOperandInterface $sort The ordering expression.
     * @param string $order The ordering direction.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function orderBy(\PHPCR\Query\QOM\DynamicOperandInterface $sort, $order = null)
    {
        $this->state = self::STATE_DIRTY;
        if ($order == 'ASC' ) {
            $ordering = $this->qomFactory->ascending($sort);
        } else {
            $ordering = $this->qomFactory->descending($sort);
        }
        $this->orderings = array($ordering);
        return $this;
    }

    /**
     * Specifies one restriction (may be simple or composed).
     * Replaces any previously specified restrictions, if any.
     *
     * @param PHPCR\Query\QOM\ConstraintInterface $constraint
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function where($constraint)
    {
        $this->state = self::STATE_DIRTY;
        $this->constraint = $constraint;
        return $this;
    }

    /**
     * Returns the constraint to apply
     *
     * @return PHPCR\Query\QOM\ConstraintInterface the constraint to be applied
     */
    public function getConstraint()
    {
        return $this->constraint;
    }

    /**
     * Creates a new constraint formed by applying a logical AND to the
     * existing constraint and the new one
     *
     * Order of ands is important:
     *
     * Given $this->constraint = $constraint1
     * running andWhere($constraint2)
     * resulting constraint will be $constraint1 AND $constraint2
     *
     * If there is no previous constraint then it will simply store the
     * provided one
     *
     * @param PHPCR\Query\QOM\ConstraintInterface $constraint
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function andWhere(\PHPCR\Query\QOM\ConstraintInterface $constraint)
    {
        $this->state = self::STATE_DIRTY;
        if ($this->constraint) {
            $this->constraint = $this->qomFactory->_and($this->constraint, $constraint);
        } else {
            $this->constraint = $constraint;
        }
        return $this;
    }

    /**
     * Creates a new constraint formed by applying a logical OR to the
     * existing constraint and the new one
     *
     * Order of ands is important:
     *
     * Given $this->constraint = $constraint1
     * running orWhere($constraint2)
     * resulting constraint will be $constraint1 OR $constraint2
     *
     * If there is no previous constraint then it will simply store the
     * provided one
     *
     * @param PHPCR\Query\QOM\ConstraintInterface $constraint
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function orWhere(\PHPCR\Query\QOM\ConstraintInterface $constraint)
    {
        $this->state = self::STATE_DIRTY;
        if ($this->constraint) {
            $this->constraint = $this->qomFactory->_or($this->constraint, $constraint);
        } else {
            $this->constraint = $constraint;
        }
        return $this;
    }

    /**
     * Returns the columns to be selected
     *
     * @return array The columns to be selected
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Identifies a property in the specified or default selector to include in the tabular view of query results.
     * Replaces any previously specified columns to be selected if any.
     *
     * @param string $propertyName
     * @param string $columnName
     * @param string $selectorName
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function select($propertyName, $columnName, $selectorName)
    {
        $this->state = self::STATE_DIRTY;
        $this->columns = array($this->qomFactory->column($propertyName, $columnName, $selectorName));
        return $this;
    }

    /**
     * Adds a property in the specified or default selector to include in the tabular view of query results.
     *
     * @param string $propertyName
     * @param string $columnName
     * @param string $selectorName
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function addSelect($propertyName, $columnName, $selectorName)
    {
        $this->state = self::STATE_DIRTY;
        $this->columns[] = $this->qomFactory->column($propertyName, $columnName, $selectorName);
        return $this;
    }

    /**
     * Sets the default Selector or the node-tuple Source. Can be a selector
     * or a join.
     *
     * @param SourceInterface $source
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function from(\PHPCR\Query\QOM\SourceInterface $source)
    {
        $this->state = self::STATE_DIRTY;
        $this->source = $source;
        return $this;
    }

    /**
     * Gets the default Selector.
     *
     * @return PHPCR\Query\QOM\SourceInterface The default selector.
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Performs an inner join between the stored source and the supplied source.
     *
     * @param \PHPCR\Query\QOM\SourceInterface $rightSource
     * @param string $joinType as specified in PHPCR\Query\QOM\QueryObjectModelConstantsInterface
     * @param string $joinCondition
     * @return QueryBuilder This QueryBuilder instance.
     * @trows RuntimeException if there is not an existing source.
     */
    public function join(\PHPCR\Query\QOM\SourceInterface $rightSource, \PHPCR\Query\QOM\JoinConditionInterface $joinCondition)
    {
        return $this->innerJoin($rightSource, $joinCondition);
    }

    /**
     * Performs an inner join between the stored source and the supplied source.
     *
     * @param \PHPCR\Query\QOM\SourceInterface $rightSource
     * @param string $joinType as specified in PHPCR\Query\QOM\QueryObjectModelConstantsInterface
     * @param string $joinCondition
     * @return QueryBuilder This QueryBuilder instance.
     * @trows RuntimeException if there is not an existing source.
     */
    public function innerJoin(\PHPCR\Query\QOM\SourceInterface $rightSource, \PHPCR\Query\QOM\JoinConditionInterface $joinCondition)
    {
        return $this->joinWithType($rightSource, \PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_JOIN_TYPE_INNER, $joinCondition);
    }

    /**
     * Performs an left outer join between the stored source and the supplied source.
     *
     * @param \PHPCR\Query\QOM\SourceInterface $rightSource
     * @param string $joinType as specified in PHPCR\Query\QOM\QueryObjectModelConstantsInterface
     * @param string $joinCondition
     * @return QueryBuilder This QueryBuilder instance.
     * @trows RuntimeException if there is not an existing source.
     */
    public function leftJoin(\PHPCR\Query\QOM\SourceInterface $rightSource, \PHPCR\Query\QOM\JoinConditionInterface $joinCondition)
    {
        return $this->joinWithType($rightSource, \PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_JOIN_TYPE_LEFT_OUTER, $joinCondition);
    }

    /**
     * Performs a right outer join between the stored source and the supplied source.
     *
     * @param \PHPCR\Query\QOM\SourceInterface $rightSource
     * @param string $joinType as specified in PHPCR\Query\QOM\QueryObjectModelConstantsInterface
     * @param string $joinCondition
     * @return QueryBuilder This QueryBuilder instance.
     * @trows RuntimeException if there is not an existing source.
     */
    public function rightJoin(\PHPCR\Query\QOM\SourceInterface $rightSource, \PHPCR\Query\QOM\JoinConditionInterface $joinCondition)
    {
        return $this->joinWithType($rightSource, \PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_JOIN_TYPE_RIGHT_OUTER, $joinCondition);
    }

    /**
     * Performs an join between the stored source and the supplied source.
     *
     * @param \PHPCR\Query\QOM\SourceInterface $rightSource
     * @param string $joinType as specified in PHPCR\Query\QOM\QueryObjectModelConstantsInterface
     * @param string $joinCondition
     * @return QueryBuilder This QueryBuilder instance.
     * @trows RuntimeException if there is not an existing source.
     */
    public function joinWithType(\PHPCR\Query\QOM\SourceInterface $rightSource, $joinType, \PHPCR\Query\QOM\JoinConditionInterface $joinCondition)
    {
        if (!$this->source) {
            throw new \RuntimeException('Cannot perform a join without a previous call to from');
        }
        $this->state = self::STATE_DIRTY;
        $this->source = $this->qomFactory->join($this->source, $rightSource, $joinType, $joinCondition);
        return $this;
    }

    /**
     * Gets the query built
     *
     * @return \PHPCR\Query\QueryObjectModelInterface
     * @return PHPCR\Query\QueryInterface
     */
    public function getQuery()
    {
        if ($this->query !== null && $this->state === self::STATE_CLEAN) {
            return $this->query;
        }
        $this->state = self::STATE_CLEAN;
        $this->query = $this->qomFactory->createQuery($this->source, $this->constraint, $this->orderings, $this->columns);
        return $this->query;
    }

    /**
     * Executes the query setting firstResult and maxResults.
     *
     * @return PHPCR\Query\QueryResultInterface
     */
    public function execute()
    {
        if ($this->query === null || $this->state === self::STATE_DIRTY) {
            $this->query = $this->getQuery();
        }

        if ($this->firstResult) {
            $this->query->setOffset($this->firstResult);
        }

        if ($this->maxResults()) {
            $query->setLimit($this->maxResults);
        }
        $queryResult = $query->execute();

        return $queryResult;
    }
}
