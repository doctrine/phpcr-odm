<?php

namespace Doctrine\ODM\PHPCR\Query;

use PHPCR\Util\QOM\QueryBuilder as BaseQueryBuilder; 
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use Doctrine\ODM\PHPCR\DocumentManager;

class QueryBuilder extends BaseQueryBuilder
{
    protected $dm;
    protected $documentClass;

    public function __construct(QueryObjectModelFactoryInterface $qomFactory, DocumentManager $dm)
    {
        $this->dm = $dm;
        parent::__construct($qomFactory);
    }

    public function setDocumentClass($documentClass)
    {
        $this->documentClass = $documentClass;
    }

    public function getQuery()
    {
        $query = new Query(parent::getQuery(), $this->dm);
        $query->setDocumentClass($this->documentClass);

        return $query;
    }
}
