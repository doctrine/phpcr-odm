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
class Query implements QueryInterface
{
    protected $query;
    protected $dm;

    public function __construct(QueryInterface $query, DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->query = $query;
    }
    
    /**
     * {@inheritDoc}
     */
    public function bindValue($varName, $value)
    {
        return $this->query->bindValue($varName, $value);
    }

    /**
     * Return the results of the query as an
     * ArrayCollection of PHPCR ODM Documents. 
     *
     * @return ArrayCollection
     */
    public function getResults()
    {
        return $this->dm->getDocumentsByQuery($this->query);
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        return $this->query->execute();
    }

    /**
     * {@inheritDoc}
     */
    public function getBindVariableNames()
    {
        return $this->query->getBindVariableNames();
    }

    /**
     * {@inheritDoc}
     */
    public function setLimit($limit)
    {
        return $this->query->setLimit($limit);
    }

    /**
     * {@inheritDoc}
     */
    public function setOffset($offset)
    {
        return $this->query->setOffset($offset);
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
