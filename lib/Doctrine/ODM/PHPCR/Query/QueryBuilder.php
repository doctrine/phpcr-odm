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

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface;
use PHPCR\Query\QOM\JoinConditionInterface;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Query\Query;

/**
 * Class to programatically construct query objects for the PHPCR ODM.
 *
 * @todo: Joins, Cloning, Parameter binding
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class QueryBuilder
{
    const STATE_DIRTY = 0;
    const STATE_CLEAN = 1;

    const TYPE_SELECT = 0;

    protected $dm;
    protected $qomf;
    protected $state = self::STATE_CLEAN;
    protected $parameters = array();
    protected $parts = array(
        'select'   => array(),
        'nodeType' => null,
        'join'     => array(),
        'where'    => null,
        'orderBy'  => array(),
        'from'     => null,
    );
    protected $query;
    protected $firstResult;
    protected $maxResults;
    protected $expr;
    protected $exprVisitor;

    /**
     * Initializes a new QueryBuilder
     *
     * @param Doctrine\ODM\PHPCR\DocumentManager
     * @param PHPCR\Query\QOM\QueryObjectModelFactoryInterface
     * @param Doctrine\ODM\PHPCR\Query\ExpressionBuilder - inject for test cases
     * @param Doctrine\ODM\PHPCR\Query\PhpcrExpressionVisitor - inject for test cases
     */
    public function __construct(
        DocumentManager $dm,
        QueryObjectModelFactoryInterface $qomf,
        ExpressionBuilder $expr = null,
        PhpcrExpressionVisitor $exprVisitor = null
    )
    {
        $this->dm = $dm;
        $this->qomf = $qomf;
        $this->expr = $expr ? $expr : new ExpressionBuilder;
        $this->exprVisitor = $exprVisitor ? $exprVisitor : new PhpcrExpressionVisitor($this->qomf);
    }

    /**
     * Return an ExpressionBuilder instance.
     *
     * @return ExpressionBuilder
     */
    public function expr()
    {
        return $this->expr;
    }

    /**
     * Return the query type.
     *
     * @return integer
     */
    public function getType()
    {
        return self::TYPE_SELECT;
    }

    /**
     * Return the document manager
     *
     * @return DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->dm;
    }

    /**
     * Return the dirty state of this query builder,
     * e.g. QueryBuilder::STATE_DIRTY or QueryBuilder:STATE_DIRTY
     *
     * @return integer
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Constructs a query instance from the current specification of
     * the query builder.
     *
     * @return Query
     */
    public function getQuery()
    {
        if ($this->query !== null && $this->state === self::STATE_CLEAN) {
            return $this->query;
        }

        $from = $this->getPart('from');
        $nodeType = $this->getPart('nodeType');

        if (null === $from && null === $nodeType) {
            $nodeType = $this->qomf->selector('nt:base');
        }

        if ($from && $nodeType) {
            throw QueryBuilderException::cannotSpecifyBothNodeTypeAndFrom($nodeType->getNodeTypeName(), $from);
        }

        if (null === $nodeType && $from) {
            $metadata = $this->dm->getClassMetadata($from);
            $nodeTypeName = $metadata->getNodeType();
            $nodeType = $this->qomf->selector($nodeTypeName);
        }

        if ($from) {
            $this->andWhere($this->expr()->orX(
                $this->expr()->eq('phpcr:class', $from),
                $this->expr()->eq('phpcr:classparents', $from)
            ));
        }

        $where = $this->getPart('where');
        $where = $where ? $this->exprVisitor->dispatch($where) : null;

        $this->state = self::STATE_CLEAN;
        $phpcrQuery = $this->qomf->createQuery(
            $nodeType,
            $where,
            $this->getPart('orderBy'),
            $this->getPart('select')
        );
        $this->query = new Query($phpcrQuery, $this->dm);

        if ($this->firstResult) {
            $this->query->setFirstResult($this->firstResult);
        }

        if ($this->maxResults) {
            $this->query->setMaxResults($this->maxResults);
        }

        return $this->query;
    }

    // public function getRootAlias()
    // public function getRootAliases()
    // public function getRootEntities()

    /**
     * NOT IMPLEMENTED
     *
     * Sets a parameter for the query being constructed
     *
     * @param string         $key
     * @param string|integer $value
     *
     * @return QueryBuilder - this query builder instance
     */
    public function setParameter($key, $value)
    {
        throw QueryBuilderException::notYetSupported(__METHOD__, '@todo: Parameter binding not supported by jackalope ...');

        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * NOT IMPLEMENTED
     *
     * Sets the parameters used in the query being constructed
     * Note: Will overwrite any existing parameters.
     *
     * @param array $parameters The parameters to set.
     *
     * @return QueryBuilder this query builder instance
     */
    public function setParameters($parameters)
    {
        throw QueryBuilderException::notYetSupported(__METHOD__, '@todo: Parameter binding not supported by jackalope ...');

        $this->parameters = $parameters;

        return $this;
    }

    /**
     * NOT IMPLEMENTED
     *
     * Gets the parameters used in the query being constructed
     *
     * @return array
     */
    public function getParameters()
    {
        throw QueryBuilderException::notYetSupported(__METHOD__, '@todo: Parameter binding not supported by jackalope ...');

        return $this->parameters;
    }

    /**
     * NOT IMPLEMENTED
     *
     * Gets a parameter for the query being constructed
     *
     * @param string $key key of parameter to get
     *
     * @return mixed|null
     */
    public function getParameter($key)
    {
        throw QueryBuilderException::notYetSupported(__METHOD__, '@todo: Parameter binding not supported by jackalope ...');

        if (isset($this->parameters[$key])) {
            return $this->parameters[$key];
        }

        return null;
    }

    /**
     * Sets the position of the first result to retrieve (the "offset").
     *
     * @param integer $firstResult The first result to return.
     *
     * @return QueryBuilder this QueryBuilder instance.
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
        return $this->firstResult;
    }

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param integer $maxResults The maximum number of results to retrieve.
     *
     * @return QueryBuilder this QueryBuilder instance.
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
     * @return QueryBuilder - this QueryBuilder instance.
     */
    public function add($partName, $part, $append = false)
    {
        if (!array_key_exists($partName, $this->parts)) {
            throw QueryBuilderException::unknownPart($partName, array_keys($this->parts));
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
     * @return QueryBuilder - this QueryBuilder instance.
     */
    public function select($propertyName, $columnName = null, $selectorName = null)
    {
        $this->add('select', $this->qomf->column($propertyName, $columnName, $selectorName));

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
     * @return QueryBuilder - this QueryBuilder instance.
     */
    public function addSelect($propertyName, $columnName = null, $selectorName = null)
    {
        $this->add('select', $this->qomf->column($propertyName, $columnName, $selectorName), true);

        return $this;
    }

    /**
     * Set the node type to select.
     *
     * NOTE: This mutually exlusive to {@link from}, i.e. you must either specify
     *       nodeType or specify {@link from}, you cannot specify both.
     *
     * @param string $nodeTypeName - Node type to select from
     * @param string $selectorName - Alias which can be used elsewhere in query (@notsure)
     *
     * @return QueryBuilder this QueryBuilder instance.
     */
    public function nodeType($nodeTypeName, $selectorName = null)
    {
        $this->add('nodeType', $this->qomf->selector($nodeTypeName, $selectorName));

        return $this;
    }

    /**
     * Set the document class to select from.
     *
     * NOTE: This mutually exlusive to {@link nodeType}, i.e. you must either specify
     *       from() or specify {@link nodeType}, you cannot specify both.
     *
     * @param string $documentFqn Full qualified document class name
     *
     * @return QueryBuilder this QueryBuilder instance
     */
    public function from($documentFqn)
    {
        $this->add('from', $documentFqn);

        return $this;
    }

    /**
     * Alias for inner join
     *
     * @return QueryBuilder this QueryBuilder instance.
     *
     * @see joinWithtype
     */
    public function join($nodeTypeName, $selectorName, JoinConditionInterface $joinCondition)
    {
        return $this->innerJoin($nodeTypeName, $selectorName, $joinCondition);
    }

    /**
     * NOT IMPLEMENTED
     *
     * @return QueryBuilder this QueryBuilder instance.
     *
     * @see joinWithtype
     */
    public function innerJoin($nodeTypeName, $selectorName, JoinConditionInterface $joinCondition)
    {
        return $this->joinWithType($nodeTypeName, $selectorName, QueryObjectModelConstantsInterface::JCR_JOIN_TYPE_INNER, $joinCondition);
    }

    /**
     * NOT IMPLEMENTED
     *
     * @return QueryBuilder this QueryBuilder instance.
     *
     * @see joinWithtype
     */
    public function leftJoin($nodeTypeName, $selectorName, JoinConditionInterface $joinCondition)
    {
        return $this->joinWithType($nodeTypeName, $selectorName, QueryObjectModelConstantsInterface::JCR_JOIN_TYPE_LEFT_OUTER, $joinCondition);
    }

    /**
     * NOT IMPLEMENTED
     *
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
     * @param string $joinType     - Join type
     *
     * @return QueryBuilder - this QueryBuilder instance.
     *
     * @throws QueryBuilderException if the source is not yet set
     */
    public function joinWithType($nodeTypename, $selectorName, $joinType, JoinConditionInterface $joinCondition)
    {
        throw QueryBuilderException::notYetSupported(__METHOD__, 'Joins not supported in QueryBuilder yet. Need some good test cases.');

        if (!$this->source) {
            throw QueryBuilderException::cannotJoinWithNoFrom();
        }

        $rightSource = $this->qomf->selector($nodeTypeName, $selectorName);

        $this->state = self::STATE_DIRTY;
        $this->add('from', $this->qomf->join(
            $this->getPart('from'),
            $rightSource,
            $joinType,
            $joinCondition
        ));

        return $this;
    }

    // public function set($key, $value)

    /**
     * Set the expression/criteria used for this query.
     * The Contraint can be easily obtained throught the ExpressionBuilder
     *
     * <code>
     *   $qb->where($qb->expr()->eq('foo', 'bar'));
     * </codeE>
     *
     * Overwrites any existing "where's"
     *
     * @param Expression $expression Expression to apply to query.
     *
     * @return QueryBuilder this QueryBuilder instance.
     */
    public function where(Expression $expression)
    {
        $this->add('where', $expression);

        return $this;
    }

    /**
     * Creates a new expression formed by applying a logical AND to the
     * existing expression and the new one
     *
     * Order of ands is important:
     *
     * Given $this->expression = $expression1
     * running andWhere($expression2)
     * resulting expression will be $expression1 AND $expression2
     *
     * If there is no previous expression then it will simply store the
     * provided one
     *
     * @param Expression $expression
     *
     * @return QueryBuilder this QueryBuilder instance.
     */
    public function andWhere(Expression $expression)
    {
        if ($existingExpression = $this->getPart('where')) {
            $this->add('where', $this->expr()->andX($existingExpression, $expression));
        } else {
            $this->add('where', $expression);
        }

        return $this;
    }

    /**
     * Creates a new expression formed by applying a logical OR to the
     * existing expression and the new one
     *
     * Order of ands is important:
     *
     * Given $this->expression = $expression1
     * running orWhere($expression2)
     * resulting expression will be $expression1 OR $expression2
     *
     * If there is no previous expression then it will simply store the
     * provided one
     *
     * @param Expression $expression
     *
     * @return QueryBuilder this QueryBuilder instance.
     */
    public function orWhere(Expression $expression)
    {
        if ($existingExpression = $this->getPart('where')) {
            $this->add('where', $this->expr()->orX($existingExpression, $expression));
        } else {
            $this->add('where', $expression);
        }

        return $this;
    }

    /**
     * Sets the ordering of the query results.
     *
     * @param array|string $propertyName Either an array of or single property name value.
     * @param string       $order        The ordering direction - [ASC|DESC]
     *
     * @return QueryBuilder this QueryBuilder instance.
     */
    public function orderBy($propertyName, $order = 'ASC')
    {
        $this->resetPart('orderBy');

        if (is_array($propertyName)) {
            foreach ($propertyName as $ordering) {
                $this->addOrderBy($ordering, $order);
            }

            return $this;
        }

        return $this->addOrderBy($propertyName, $order);
    }

    /**
     * Adds an ordering to the query results
     *
     * @param string $propertyName Property name
     * @param string $order        The ordering direction - [ASC|DESC]
     *
     * @return QueryBuilder this QueryBuilder instance.
     */
    public function addOrderBy($propertyName, $order = null)
    {
        $sort = $this->qomf->propertyValue($propertyName);
        $order = strtoupper($order);

        if ($order == 'DESC') {
            $ordering = $this->qomf->descending($sort);
        } else {
            $ordering = $this->qomf->ascending($sort);
        }

        $this->add('orderBy', $ordering, true);

        return $this;
    }

    /**
     * Return a query part
     *
     * @param string $partName
     *
     * @return mixed The query part
     */
    public function getPart($partName)
    {
        return $this->parts[$partName];
    }

    /**
     * Return all the query parts
     *
     * @return array All the query parts
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * Reset parts
     *
     * @param array $parts
     *
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
     *
     * @return QueryBuilder
     */
    public function resetPart($part)
    {
        $this->parts[$part] = is_array($this->parts[$part]) ? array() : null;
        $this->state = self::STATE_DIRTY;

        return $this;
    }

    public function __toString()
    {
        return (string) $this->getQuery()->getStatement();
    }

    /**
     * NOT IMPLEMENTED
     */
    public function __clone()
    {
        throw QueryBuilderException::notYetSupported(__METHOD__, 'Cloning not yet supported');
    }
}
