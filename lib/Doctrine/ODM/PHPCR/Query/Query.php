<?php

namespace Doctrine\ODM\PHPCR\Query;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryResultInterface;

/**
 * Query.
 *
 * Wraps the given PHPCR query object in the ODM, emulating
 * the Query object from the ORM.
 *
 * @author Daniel Leech <daniel@dantleech.comqq
 */
class Query
{
    public const HYDRATE_DOCUMENT = 1;

    public const HYDRATE_PHPCR = 2;

    private int $hydrationMode = self::HYDRATE_DOCUMENT;
    private array $parameters = [];
    private ?string $primaryAlias;
    private ?int $firstResult = null;
    private ?int $maxResults = null;
    private ?string $documentClass = null;
    private QueryInterface $query;
    private DocumentManagerInterface $dm;

    public function __construct(QueryInterface $query, DocumentManagerInterface $dm, $primaryAlias = null)
    {
        $this->dm = $dm;
        $this->query = $query;
        $this->primaryAlias = $primaryAlias;
    }

    /**
     * Defines the processing mode to be used during hydration / result set transformation.
     *
     * @param int $hydrationMode One of the self::HYDRATE_* constants
     *
     * @throws QueryException if $hydrationMode is not known
     *
     * @see execute
     */
    public function setHydrationMode(int $hydrationMode): self
    {
        if (self::HYDRATE_DOCUMENT !== $hydrationMode && self::HYDRATE_PHPCR !== $hydrationMode) {
            throw QueryException::hydrationModeNotKnown($hydrationMode);
        }

        $this->hydrationMode = $hydrationMode;

        return $this;
    }

    public function getHydrationMode(): int
    {
        return $this->hydrationMode;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Replace all parameters.
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function setParameter(string $key, $value): self
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * @return mixed|null the value of the bound parameter
     */
    public function getParameter(string $key)
    {
        return $this->parameters[$key] ?? null;
    }

    /**
     * Set the document class to use when using HYDRATION_DOCUMENT.
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
     */
    public function setDocumentClass(string $documentClass): self
    {
        $this->documentClass = $documentClass;

        return $this;
    }

    /**
     * Executes the query and returns the result based on the hydration mode.
     *
     * @param array|null $parameters    parameters, alternative to calling setParameters
     * @param int|null   $hydrationMode Processing mode to be used during the hydration
     *                                  process. One of the Query::HYDRATE_* constants.
     *
     * @return Collection|QueryResultInterface A Collection for HYDRATE_DOCUMENT, QueryResultInterface for HYDRATE_PHPCR
     *
     * @throws QueryException if $hydrationMode is not known
     */
    public function execute(array $parameters = null, int $hydrationMode = null)
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
     * @return Collection|QueryResultInterface
     */
    public function getResult(int $hydrationMode = self::HYDRATE_DOCUMENT)
    {
        return $this->execute(null, $hydrationMode);
    }

    /**
     * Gets the phpcr node results for the query.
     *
     * Alias for execute(null, HYDRATE_PHPCR).
     *
     * @return QueryResultInterface
     */
    public function getPhpcrNodeResult()
    {
        return $this->execute(null, self::HYDRATE_PHPCR);
    }

    /**
     * Get exactly one result or null.
     *
     * @throws QueryException if more than one result found
     */
    public function getOneOrNullResult(int $hydrationMode = null)
    {
        $result = $this->execute(null, $hydrationMode);

        if (count($result) > 1) {
            throw QueryException::nonUniqueResult();
        }
        if (count($result) <= 0) {
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
     * @throws QueryException if no result or more than one result found
     */
    public function getSingleResult(int $hydrationMode = null)
    {
        $result = $this->getOneOrNullResult($hydrationMode);

        if (null === $result) {
            throw new NoResultException();
        }

        return $result;
    }

    /**
     * Sets the maximum number of results to retrieve (the "limit").
     */
    public function setMaxResults(int $maxResults): self
    {
        $this->maxResults = $maxResults;

        return $this;
    }

    /**
     * Gets the maximum number of results the query object was set to retrieve
     * (the "limit"). Returns NULL if {@link setMaxResults} was not applied to
     * this query.
     */
    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    /**
     * Sets the position of the first result to retrieve (the "offset").
     */
    public function setFirstResult(int $firstResult): self
    {
        $this->firstResult = $firstResult;

        return $this;
    }

    /**
     * Gets the position of the first result the query object was set to
     * retrieve (the "offset"). Returns NULL if {@link setFirstResult} was not
     * applied to this query.
     */
    public function getFirstResult(): ?int
    {
        return $this->firstResult;
    }

    /**
     * Proxy method to return statement of the wrapped PHPCR Query.
     */
    public function getStatement(): string
    {
        return $this->query->getStatement();
    }

    /**
     * Proxy method to return language of the wrapped PHPCR Query.
     */
    public function getLanguage(): string
    {
        return $this->query->getLanguage();
    }

    /**
     * Return wrapped PHPCR query object.
     */
    public function getPhpcrQuery(): QueryInterface
    {
        return $this->query;
    }
}
