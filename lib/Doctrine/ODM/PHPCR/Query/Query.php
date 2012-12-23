<?php

namespace Doctrine\ODM\PHPCR\Query;

use PHPCR\Query\QueryInterface;
use Doctrine\ODM\PHPCR\DocumentManager;

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
     * @inherit
     */
    function bindValue($varName, $value)
    {
        return $this->query->bindValue($varName, $value);
    }

    /**
     * @inherit
     */
    function execute()
    {
        return $this->dm->getDocumentsByQuery($this->query);
    }

    /**
     * @inherit
     */
    function getBindVariableNames()
    {
        return $this->query->getBindVariableNames();
    }

    /**
     * @inherit
     */
    function setLimit($limit)
    {
        return $this->query->setLimit($limit);
    }

    /**
     * @inherit
     */
    function setOffset($offset)
    {
        return $this->query->setOffset($offset);
    }

    /**
     * @inherit
     */
    function getStatement()
    {
        return $this->query->getStatement();
    }

    /**
     * @inherit
     */
    function getLanguage()
    {
        return $this->query->getLanguage();
    }

    /**
     * @inherit
     */
    function getStoredQueryPath()
    {
        return $this->query->getStoredQueryPath();
    }

    /**
     * @inherit
     */
    function storeAsNode($absPath)
    {
        return $this->query->storeAsNode($absPath);
    }
}
