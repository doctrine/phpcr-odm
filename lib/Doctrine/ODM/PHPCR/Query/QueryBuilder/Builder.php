<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use Doctrine\ODM\PHPCR\DocumentManager;

class Builder extends AbstractNode
{
    protected $converter;
    protected $firstResult;
    protected $maxResults;

    public function setConverter(BuilderConverterPhpcr $converter)
    {
        $this->converter = $converter;
    }

    protected function getConverter()
    {
        if (!$this->converter) {
            throw new \RuntimeException('No query converter has been set on Builder node.');
        }

        return $this->converter;
    }

    public function getQuery()
    {
        return $this->getConverter()->getQuery($this);
    }

    public function getCardinalityMap()
    {
        return array(
            'Select' => array(0, null),    // 1..*
            'From' => array(1, 1),         // 1..1
            'Where' => array(0, 1),     // 0..1
            'OrderBy' => array(0, null),   // 0..*
        );
    }

    public function where()
    {
        return $this->addChild(new Where($this));
    }

    public function from()
    {
        return $this->addChild(new From($this));
    }

    public function select()
    {
        return $this->addChild(new Select($this));
    }

    public function orderBy()
    {
        return $this->addChild(new OrderBy($this));
    }

    public function getFirstResult() 
    {
        return $this->firstResult;
    }
    
    public function setFirstResult($firstResult)
    {
        $this->firstResult = $firstResult;
    }

    public function getMaxResults() 
    {
        return $this->maxResults;
    }
    
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
    }
}
