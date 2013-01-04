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

namespace Doctrine\ODM\PHPCR\Query;

use PHPCR\Util\QOM\QueryBuilder as BaseQueryBuilder;
use PHPCR\Query\QOM\QueryObjectModelInterface;
use PHPCR\Query\QOM\ComparisonInterface;
use Doctrine\Common\Collections\Expr\ExpressionBuilder;

/**
 * @author Daniel Leech <daniel@dantleech.com>
 */
class QueryBuilder
{
    const STATE_DIRTY = 0;
    const STATE_CLEAN = 1;

    protected $dm;
    protected $qomf;
    protected $state = self::STATE_CLEAN;
    protected $parameters = array();
    protected $parts = array(
        'select'  => array(),
        'from'    => null,
        'join'    => array(),
        'where'   => array(),
        'orderBy' => array(),
    );

    public function __construct(DocumentManager $dm, QueryObjectManagerInterface $qom)
    {
        $this->dm = $dm;
        parent::__construct($qom);
    }

    public function expr()
    {
        return new ExpressionBuilder;
    }

    // public function getType()
    
    public function getDocumentManager()
    {
        return $this->dm;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getQuery()
    {
        if ($this->query !== null && $this->state === self::STATE_CLEAN) {
            return $this->query;
        }
        $this->state = self::STATE_CLEAN;
        $this->query = $this->qomFactory->createQuery($this->source, $this->constraint, $this->orderings, $this->columns);

        if ($this->firstResult) {
            $this->query->setOffset($this->firstResult);
        }

        if ($this->maxResults) {
            $this->query->setLimit($this->maxResults);
        }

        return $this->query;
    }

    // public function getRootAlias()
    // public function getRootAliases()
    // public function getRootEntities()

    /**
     * Sets a parameter for the query being constructed
     *
     * @param string $key
     * @param string|integer $value
     *
     * @return QueryBuilder - this query builder instance
     */
    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * Sets the parameters used in the query being constructed
     * Note: Will overwrite any existing parameters.
     *
     * @todo: ORM uses Common\ArrayCollection and ORM\Query\Parameter
     *        Should probably do the same here (move ORM\Query\Parameter to Common ?)
     *
     * @param array $parameters - The parameters to set.
     *
     * @return QueryBuilder - this query builder instance
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Gets the parameters used in the query being constructed
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
        
    /**
     * Gets a parameter for the query being constructed
     *
     * @param string $key - key of parameter to get
     *
     * @return QueryBuilder - this query builder instance
     */
    public function getParameter($key)
    {
        return $this->parameters[$key];
    }

    /**
     * Sets the position of the first result to retrieve (the "offset").
     *
     * @param integer $firstResult The first result to return.
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function setFirstResult($firstResult)
    {
        $this->firstResult = $firstResult;

        return $this;
    }

    /**
     * Gets the position of the first result the query object was set to retrieve (the "offset").
     * Returns NULL if {@link setFirstResult} was not applied to this QueryBuilder.
     *
     * @return integer The position of the first result.
     */
    public function getFirstResult()
    {
        return $this->getFirstResult();
    }

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param integer $maxResults The maximum number of results to retrieve.
     * @return QueryBuilder This QueryBuilder instance.
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
     * Either appends to or replaces a single, generic query part.
     *
     * @param string $partName
     * @param string $part
     * @param string $append
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function add($partName, $part, $append = false)
    {
        if (!isset($this->parts[$part])) {
            throw QueryBuilderException::unknownPart($part, array_keys($this->parts));
        }

        $isMultiple = is_array($this->parts[$partName]);

        if ($append && $isMultiple) {
            if (is_array($part)) {
                $key = key($part);

                $this->parts[$partName][$key][] = $part[$key];
            } else {
                $this->parts[$partName][] = $part;
            }
        } else {
            $this->parts[$partName] = $isMultiple ? array($part) : $part;
        }

        $this->state = self::STATE_DIRTY;

        return $this;
    }
    
    /**
     * Identifies a property in the specified or default selector to include in the tabular view of query results.
     * Replaces any previously specified columns to be selected if any.
     *
     * @param string $propertyName
     * @param string $columnName
     * @param string $selectorName
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function select($propertyName, $columnName = null, $selectorName = null)
    {
        $this->addPart('select', $this->qomf->column($propertyName, $columnName, $selectorName));

        return $this;
    }

    // public function distinct($flag = true)
    
    /**
     * Adds a property in the specified or default selector to include in the tabular view of query results.
     *
     * @param string $propertyName
     * @param string $columnName
     * @param string $selectorName
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function addSelect($propertyName, $columnName = null, $selectorName = null)
    {
        $this->addPart('select', $this->qomf->column($propertyName, $columnName, $selectorName), true);

        return $this;
    }
    
    // public function delete($delete = null, $alias = null)
    // public function update($update = null, $alias = null)

    /**
     * Set the node type to select "from".
     *
     * @note: This makes me wonder if we should have more support for
     *        Node Types in the ODM, e.g. a standard way to define the node type
     *        schema.
     *
     * <code>
     *     $qb = $dm->createQueryBuilder()
     *         ->from('nt:unstructured', 'unstructured')
     * </code>
     *
     * @param string $nodeTypeName - Node type to select from
     * @param string $selectorName - Alias which can be used elsewhere in query (@notsure)
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function from($nodeTypeName, $selectorName = null)
    {
        $this->add('from', $this->qomf->selector($nodeTypeName, $selectorName));

        return $this;
    }

    /**
     * @see joinWithtype
     *
     * @return QueryBuilder This QueryBuilder instance.
     *
     * @throws QueryBuilderException if there is not an existing source.
     */
    public function join($nodeTypeName, $selectorName, JoinConditionInterface $joinCondition)
    {
        return $this->innerJoin($nodeTypeName, $selectorName);
    }

    /**
     * @see joinWithtype
     *
     * @return QueryBuilder This QueryBuilder instance.
     *
     * @throws QueryBuilderException if there is not an existing source.
     */
    public function innerJoin($nodeTypeName, $selectorName, JoinConditionInterface $joinCondition)
    {
        return $this->joinWithType($nodeTypeName, $selectorName, QueryObjectModelConstantsInterface::JCR_JOIN_TYPE_INNER, $joinCondition);
    }

    /**
     * @see joinWithtype
     *
     * @return QueryBuilder This QueryBuilder instance.
     *
     * @throws QueryBuilderException if there is not an existing source.
     */
    public function leftJoin($nodeTypeName, $selectorName, JoinConditionInterface $joinCondition)
    {
        return $this->joinWithType($nodeTypename, $selectorName, QueryObjectModelConstantsInterface::JCR_JOIN_TYPE_LEFT_OUTER, $joinCondition);
    }

    /**
     * Joins ...
     *
     * @notsure - SelectorInterface: I have dropped the SelectorInterface and replaced it with
     *            the parameters $nodeTypeName and $selectorName, which /i think/
     *            correlate to the ORMs $join and $alias arguments.
     *
     *            Does this break anything? Do we lose any flexibility?
     *
     * @notsure - $jointype: These constants are currently coming from PHPCR. We should
     *            probably wrap them in the ODM to be consistent.
     *
     * @notsure - $joinCondition: I guess that this would some sort of \Expr class, will wait
     *            and see.
     *
     *            Or maybe something more intuitive -- methods joinDescendantNodes(), joinChildNodes(), joinSameNodes() ?
     *
     * @param string $nodeTypeName - Name of node type to join with
     * @param string $selectorName - Alias for node type
     * @param string $joinType - Join type
     *
     * @return QueryBuilder This QueryBuilder instance.
     *
     * @throws QueryBuilderException if there is not an existing source.
     */
    public function joinWithType($nodeTypename, $selectorName, $joinType, JoinConditionInterface $joinCondition)
    {
        if (!$this->source) {
            throw QueryBuilderException::cannotJoinWithNoFrom();
        }

        $rightSource = $this->qomf->selector($nodeTypeName, $selectorName);

        $this->state = self::STATE_DIRTY;
        $this->add('from', $this->qomFactory->join(
            $this->getPart('from'), 
            $rightSource, 
            $joinType, 
            $joinCondition
        ));

        return $this;
    }

    // public function set($key, $value)

    /**
     * Set the constraint/criteria used for this query.
     * The Contraint can be easily obtained throught the ExpressionBuilder
     *
     * <code>
     *   $qb->where($qb->expr()->eq('foo', 'bar'));
     * </codeE>
     *
     * Overwrites any existing "where's"
     *
     * @param $constraint ConstraintInterface - Constraint/criteria to apply to query.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function where(ConstraintInterface $constraint)
    {
        $this->add('where', $constraint);

        return $this;
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
     * @param ConstraintInterface $constraint
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function andWhere(ConstraintInterface $constraint)
    {
        if ($existingConstraint = $this->getPart('where')) {
            $this->add('where', $this->qomFactory->andConstraint($existingConstraint, $constraint));
        } else {
            $this->add('where', $constraint);
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
     * @param ConstraintInterface $constraint
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function orWhere(ConstraintInterface $constraint)
    {
        if ($existingConstraint = $this->getPart('where')) {
            $this->add('where', $this->qomFactory->orConstraint($existingConstraint, $constraint));
        } else {
            $this->add('where', $constraint);
        }

        return $this;
    }

    // public function groupBy($groupBy)
    // public function addGroupBy($groupBy)
    // public function having($having)
    // public function andHaving($having)
    // public function orHaving($having)
        
    /**
     * Adds an ordering to the query results.
     *
     * @param DynamicOperandInterface $sort  The ordering expression.
     * @param string                  $order The ordering direction.
     *
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function orderBy(DynamicOperandInterface $sort, $order = 'ASC')
    {
        return $this->addOrderBy($sort, $order);
    }

    public function addOrderBy($sort, $order = null)
    {
        $order = strtoupper($order);

        if ($order == 'DESC') {
            $ordering = $this->qomFactory->descending($sort);
        } else {
            $ordering = $this->qomFactory->ascending($sort);
        }

        $this->addPart('orderBy', $ordering, true);

        return $this;
    }

    public function getPart($partName)
    {
        return $this->parts[$partName];
    }

    public function getParts()
    {
        return $this->parts;
    }

    /**
     * Reset parts
     *
     * @param array $parts
     * @return QueryBuilder
     */
    public function resetParts($parts = null)
    {
        if (is_null($parts)) {
            $parts = array_keys($this->parts);
        }

        foreach ($parts as $part) {
            $this->resetPart($part);
        }

        return $this;
    }

    /**
     * Reset single DQL part
     *
     * @param string $part
     * @return QueryBuilder;
     */
    public function resetPart($part)
    {
        $this->parts[$part] = is_array($this->parts[$part]) ? array() : null;
        $this->state = self::STATE_DIRTY;

        return $this;
    }

    public function __toString()
    {
        return $this->getQuery()->getStatement();
    }

    public function __clone()
    {
    }
}
