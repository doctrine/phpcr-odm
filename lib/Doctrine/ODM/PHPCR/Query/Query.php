<?php

namespace Doctrine\ODM\PHPCR\Query;

use PHPCR\Query\QueryInterface;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Query
 *
 * Wraps the given PHPCR query object in the ODM, emulating
 * the Query object from the ORM.
 *
 * @author Daniel Leech <daniel@dantleech.comqq
 */
class Query
{
    const HYDRATE_DOCUMENT = 1;
    const HYDRATE_PHPCR = 2;

    protected $hydrationMode = self::HYDRATE_DOCUMENT;
    protected $parameters = array();
    protected $primaryAlias;
    protected $firstResult;
    protected $maxResults;
    protected $documentClass;

    /**
     * @var QueryInterface
     */
    protected $query;

    /**
     * @var DocumentManagerInterface
     */
    protected $dm;

    public function __construct(QueryInterface $query, DocumentManagerInterface $dm, $primaryAlias = null)
    {
        $this->dm = $dm;
        $this->query = $query;
        $this->primaryAlias = $primaryAlias;
    }

    /**
     * Defines the processing mode to be used during hydration / result set transformation.
     *
     * @param string $hydrationMode The mode to return the result in execute.
     *
     * @return Query This query instance.
     *
     * @throws QueryException If $hydrationMode is not known.
     *
     * @see execute
     */
    public function setHydrationMode($hydrationMode)
    {
        if (self::HYDRATE_DOCUMENT !== $hydrationMode && self::HYDRATE_PHPCR !== $hydrationMode) {
            throw QueryException::hydrationModeNotKnown($hydrationMode);
        }

        $this->hydrationMode = $hydrationMode;

        return $this;
    }

    /**
     * Gets the hydration mode currently used by the query.
     *
     * @return string
     */
    public function getHydrationMode()
    {
        return $this->hydrationMode;
    }

    /**
     * Get all defined parameters.
     *
     * @return array The defined query parameters.
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Sets a collection of query parameters.
     *
     * @param array $parameters
     *
     * @return Query This query instance.
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Sets a query parameter.
     *
     * @param string $key   The parameter name.
     * @param mixed  $value The parameter value.
     *
     * @return Query This query instance.
     */
    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * Gets a query parameter.
     *
     * @param mixed $key The key of the bound parameter.
     *
     * @return mixed|null The value of the bound parameter.
     */
    public function getParameter($key)
    {
        if (!isset($this->parameters[$key])) {
            return null;
        }

        return $this->parameters[$key];
    }

    /**
     * Set the document class to use when using HYDRATION_DOCUMENT
     *
     * Note: This is useful in the following two cases:
     *
     *  1. You want to be sure that the ODM returns the correct document type
     *     (A query can return multiple document types, the ODM will ignore
     *     Documents not matching the type given here)
     *
     *  2. The PHPCR Node does not contain the phpcr:class metadata property.
     *
     * @param string $documentClass FQN of document class
     *
     * @return Query This query instance.
     */
    public function setDocumentClass($documentClass)
    {
        $this->documentClass = $documentClass;
    }

    /**
     * Executes the query and returns the result based on the hydration mode.
     *
     * @param array $parameters    Parameters, alternative to calling setParameters.
     * @param int   $hydrationMode Processing mode to be used during the hydration
     *                             process. One of the Query::HYDRATE_* constants.
     *
     * @return mixed A Collection for HYDRATE_DOCUMENT, \PHPCR\Query\QueryResultInterface for HYDRATE_PHPCR
     *
     * @throws QueryException If $hydrationMode is not known.
     */
    public function execute($parameters = null, $hydrationMode = null)
    {
        if (!empty($parameters)) {
            $this->setParameters($parameters);
        }

        if (null !== $hydrationMode) {
            $this->setHydrationMode($hydrationMode);
        }

        if (null !== $this->maxResults) {
            $this->query->setLimit($this->maxResults);
        }

        if (null !== $this->firstResult) {
            $this->query->setOffset($this->firstResult);
        }

        foreach ($this->parameters as $key => $value) {
            $this->query->bindValue($key, $value);
        }

        switch ($this->hydrationMode) {
            case self::HYDRATE_PHPCR:
                $data = $this->query->execute();
                break;
            case self::HYDRATE_DOCUMENT:
                $data = $this->dm->getDocumentsByPhpcrQuery($this->query, $this->documentClass, $this->primaryAlias);
                break;
            default:
                throw QueryException::hydrationModeNotKnown($this->hydrationMode);
        }

        if (is_array($data)) {
            $data = new ArrayCollection($data);
        }

        return $data;
    }

    /**
     * Gets the list of results for the query.
     *
     * Alias for execute(null, $hydrationMode = HYDRATE_DOCUMENT).
     *
     * @param int $hydrationMode One of the hydration class constants
     *
     * @return array
     */
    public function getResult($hydrationMode = self::HYDRATE_DOCUMENT)
    {
        return $this->execute(null, $hydrationMode);
    }

    /**
     * Gets the phpcr node results for the query.
     *
     * Alias for execute(null, HYDRATE_PHPCR).
     *
     * @return array
     */
    public function getPhpcrNodeResult()
    {
        return $this->execute(null, self::HYDRATE_PHPCR);
    }

    /**
     * Get exactly one result or null.
     *
     * @param int $hydrationMode
     *
     * @return mixed
     *
     * @throws QueryException if more than one result found
     */
    public function getOneOrNullResult($hydrationMode = null)
    {
        $result = $this->execute(null, $hydrationMode);

        if (count($result) > 1) {
            throw QueryException::nonUniqueResult();
        } elseif (count($result) <= 0) {
            return null;
        }

        return $result->first();
    }

    /**
     * Gets the single result of the query.
     *
     * Enforces the presence as well as the uniqueness of the result.
     *
     * If the result is not unique, a NonUniqueResultException is thrown.
     * If there is no result, a NoResultException is thrown.
     *
     * @param integer $hydrationMode
     *
     * @return mixed
     *
     * @throws QueryException if no result or more than one result found
     */
    public function getSingleResult($hydrationMode = null)
    {
        $result = $this->getOneOrNullResult($hydrationMode);

        if (null === $result) {
            throw new NoResultException();
        }

        return $result;
    }

    /**
     * Executes the query and returns an IterableResult that can be used to incrementally
     * iterate over the result.
     *
     * @param  array   $parameters    The query parameters.
     * @param  integer $hydrationMode The hydration mode to use.
     *
     * @return TODO \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    public function iterate($parameters = null, $hydrationMode = null)
    {
        throw QueryException::notImplemented(__METHOD__);
    }

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     *
     * @param integer $maxResults
     *
     * @return Query This query object.
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;

        return $this;
    }

    /**
     * Gets the maximum number of results the query object was set to retrieve
     * (the "limit"). Returns NULL if {@link setMaxResults} was not applied to
     * this query.
     *
     * @return integer Maximum number of results.
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    /**
     * Sets the position of the first result to retrieve (the "offset").
     *
     * @param integer $firstResult The first result to return.
     *
     * @return Query This query object.
     */
    public function setFirstResult($firstResult)
    {
        $this->firstResult = $firstResult;

        return $this;
    }

    /**
     * Gets the position of the first result the query object was set to
     * retrieve (the "offset"). Returns NULL if {@link setFirstResult} was not
     * applied to this query.
     *
     * @return integer The position of the first result.
     */
    public function getFirstResult()
    {
        return $this->firstResult;
    }

    /**
     * Proxy method to return statement of the wrapped PHPCR Query
     *
     * @return string The query statement.
     */
    public function getStatement()
    {
        return $this->query->getStatement();
    }

    /**
     * Proxy method to return language of the wrapped PHPCR Query
     *
     * @return string The language used
     */
    public function getLanguage()
    {
        return $this->query->getLanguage();
    }

    /**
     * Return wrapped PHPCR query object
     *
     * @return QueryInterface
     */
    public function getPhpcrQuery()
    {
        return $this->query;
    }
}
