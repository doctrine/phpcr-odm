<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use Doctrine\ODM\PHPCR\DocumentManager;

/**
 * Base QueryBuilder node.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class Builder extends AbstractNode
{
    protected $converter;
    protected $firstResult;
    protected $maxResults;

    public function getNodeType()
    {
        return self::NT_BUILDER;
    }

    public function getQuery()
    {
        return $this->getConverter()->getQuery($this);
    }

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

    public function getCardinalityMap()
    {
        return array(
            self::NT_SELECT => array(0, null),    // 1..*
            self::NT_FROM => array(1, 1),         // 1..1
            self::NT_WHERE => array(0, 1),     // 0..1
            self::NT_ORDER_BY => array(0, null),   // 0..*
        );
    }

    /**
     * @factoryMethod
     * @return Where
     */
    public function where($void = null)
    {
        $this->ensureNoArguments(__METHOD__, $void);
        return $this->setChild(new Where($this));
    }

    /**
     * @factoryMethod
     * @return WhereAnd
     */
    public function andWhere($void = null)
    {
        $this->ensureNoArguments(__METHOD__, $void);
        return $this->addChild(new WhereAnd($this));
    }

    /**
     * @factoryMethod
     * @return WhereOr
     */
    public function orWhere($void = null)
    {
        $this->ensureNoArguments(__METHOD__, $void);
        return $this->addChild(new WhereOr($this));
    }

    /**
     * @factoryMethod
     * @return From
     */
    public function from($void = null)
    {
        $this->ensureNoArguments(__METHOD__, $void);
        return $this->setChild(new From($this));
    }

    /**
     * @factoryMethod
     * @return Select
     */
    public function select($void = null)
    {
        $this->ensureNoArguments(__METHOD__, $void);
        return $this->setChild(new Select($this));
    }

    /**
     * @factoryMethod
     * @return SelectAdd
     */
    public function addSelect($void = null)
    {
        $this->ensureNoArguments(__METHOD__, $void);
        return $this->setChild(new SelectAdd($this));
    }

    /**
     * @factoryMethod
     * @return OrderBy
     */
    public function orderBy($void = null)
    {
        $this->ensureNoArguments(__METHOD__, $void);
        return $this->setChild(new OrderBy($this));
    }

    /**
     * @factoryMethod
     * @return OrderByAdd
     */
    public function addOrderBy($void = null)
    {
        $this->ensureNoArguments(__METHOD__, $void);
        return $this->addChild(new OrderByAdd($this));
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

    public function __toString()
    {
        return (string) $this->getQuery()->getStatement();
    }
}
