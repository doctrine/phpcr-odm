<?php

namespace Doctrine\ODM\PHPCR\Query;

use PHPCR\Query\QueryInterface;
use Doctrine\ODM\PHPCR\DocumentManager;

/**
 * Query
 *
 * Wraps the given PHPCR query object in the ODM
 *
 * @author Daniel Leech <daniel@dantleech.comqq
 */
class Query
{
    const HYDRATE_OBJECT = 'object';
    const HYDRATE_NONE = 'phpcr_node';
    const HYDRATE_PHPCR_NODE = 'phpcr_node';

    protected $hydrationMode = self::HYDRATE_OBJECT;
    protected $parameters = array();
    protected $query;
    protected $dm;

    public function __construct(QueryInterface $query, DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->query = $query;
    }

    /**
     * Defines the processing mode to be used during hydration / result set transformation.
     *
     * @param string $hydrationMode       Processing mode to be used during hydration process.
     *                                    One of the Query::HYDRATE_* constants.
     * @return \Doctrine\ODM\PHPCR\Query  This query instance.
     */
    public function setHydrationMode($hydartionMode) 
    {
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
     * @return \Doctrine\Common\Collections\ArrayCollection The defined query parameters.
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
     * @return \Doctrine\ODM\PHPCR\Query This query instance.
     */
    public function setParameters($parameters)
    {
        foreach ($parameters as $key => $value) {
            $this->setParameter($key, $value);
        }

        return $this;
    }
    
    /**
     * Sets a query parameter.
     *
     * @param string $key   The parameter name.
     * @param mixed $value  The parameter value.
     *
     * @return \Doctrine\ODM\PHPCR\Query This query instance.
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
     * Executes the query.
     *
     * @param array $parameters
     * @param integer $hydrationMode Processing mode to be used during the hydration process.
     * @return mixed
     */
    public function execute($parameters = null, $hydrationMode = null)
    {
        if (!empty($parameters)) {
            $this->setParameters($parameters);
        }

        if (null !== $hydrationMode) {
            $this->setHydrationMode($hydrationMode);
        }

        foreach ($this->parameters as $key => $value) {
            $this->query->bindValue($key, $value);
        }

        if ($this->hydrationMode === self::HYDRATE_NONE) {
            $data = $this->query->execute();
        } elseif ($this->hydrationMode === self::HYDRATE_OBJECT) {
            $data = $this->dm->getDocumentsByQuery($this->query);
        } else {
            throw QueryException::hydrationModeNotKnown($this->hydrationMode);
        }

        return $data;
    }

    /**
     * Gets the list of results for the query.
     *
     * Alias for execute(null, $hydrationMode = HYDRATE_OBJECT).
     *
     * @return array
     */
    public function getResult($hydrationMode = self::HYDRATE_OBJECT)
    {
        return $this->execute(null, $hydrationMode);
    }

    /**
     * Gets the phpcr node results for the query.
     *
     * Alias for execute(null, HYDRATE_PHPCR_NODE).
     *
     * @return array
     */
    public function getPhpcrNodeResult()
    {
        return $this->execute(null, self::HYDRATE_PHPCR_NODE);
    }

    /**
     * Get exactly one result or null.
     *
     * @throws NonUniqueResultException
     * @param int $hydrationMode
     * @return mixed
     */
    public function getOneOrNullResult($hydrationMode = null)
    {
        $result = $this->execute(null, $hydrationMode);

        if (!is_array($result)) {
            return $result;
        }

        if (count($result) > 1) {
            throw QueryException::nonUniqueResult();
        }

        return array_shift($result);
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
     * @return mixed
     * @throws NonUniqueResultException If the query result is not unique.
     * @throws NoResultException If the query returned no result.
     */
    public function getSingleResult($hydrationMode = null)
    {
        $result = $this->execute(null, $hydrationMode);

        if ($result) {
            throw QueryException::noResult();
        }

        if ( ! is_array($result)) {
            return $result;
        }

        if (count($result) > 1) {
            throw QueryException::nonUniqueResult();
        }

        return array_shift($result);
    }

    /**
     * Executes the query and returns an IterableResult that can be used to incrementally
     * iterate over the result.
     *
     * @param \Doctrine\Common\Collections\ArrayCollection|array $parameters The query parameters.
     * @param integer $hydrationMode The hydration mode to use.
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult
     */
    public function iterate($parameters = null, $hydrationMode = null)
    {
        throw new \Exception('Not implemented yet!');
    }

    /**
     * {@inheritDoc}
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
    }

    /**
     * {@inheritDoc}
     */
    public function setFirstResult($firstResult)
    {
        $this->firstResult = $firstResult;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatement()
    {
        return $this->query->getStatement();
    }

    /**
     * {@inheritDoc}
     */
    public function getLanguage()
    {
        return $this->query->getLanguage();
    }

    /**
     * {@inheritDoc}
     */
    public function getStoredQueryPath()
    {
        return $this->query->getStoredQueryPath();
    }

    /**
     * {@inheritDoc}
     */
    public function storeAsNode($absPath)
    {
        return $this->query->storeAsNode($absPath);
    }
}
