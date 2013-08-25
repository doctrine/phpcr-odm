<?php

namespace Doctrine\ODM\PHPCR\Query\QueryBuilder;

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use Doctrine\ODM\PHPCR\DocumentManager;

class Builder extends AbstractNode
{
    /**
     * Initializes a new (Query) Builder
     *
     * This is the root node of the builder tree and the object
     * returned by DocumentManager:createQueryBuilder
     *
     * @param Doctrine\ODM\PHPCR\DocumentManager
     * @param PHPCR\Query\QOM\QueryObjectModelFactoryInterface
     * @param Doctrine\ODM\PHPCR\Query\ExpressionBuilder - inject for test cases
     * @param Doctrine\ODM\PHPCR\Query\PhpcrExpressionVisitor - inject for test cases
     */
    public function __construct(
        DocumentManager $dm,
        QueryObjectModelFactoryInterface $qomf,
        BuilderConverterPhpcr $converter = null
    )
    {
        $this->dm = $dm;
        $this->qomf = $qomf;
        $this->converter = $converter ? $converter : new BuilderConverterPhpcr($dm->getMetadataFactory(), $qomf);
    }

    public function getQuery()
    {
        return $this->converter->getQuery($this);
    }

    public function getCardinalityMap()
    {
        return array(
            'Select' => array(0, null),    // 1..*
            'From' => array(1, 1),         // 1..1
            'Where' => array(0, null),     // 0..*
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
}
