<?php

namespace Doctrine\ODM\PHPCR\Query;

use Doctrine\Common\Collections\ExpressionBuilder as BaseExpressionBuilder;
use Doctrine\ODM\PHPCR\Query\Expression\Descendant;
use Doctrine\ODM\PHPCR\Query\Expression\Comparison;
use Doctrine\ODM\PHPCR\Query\Expression\NodeLocalName;
use Doctrine\ODM\PHPCR\Query\Expression\TextSearch;
use Doctrine\ODM\PHPCR\Query\Expression\SameNode;

class ExpressionBuilder extends BaseExpressionBuilder
{
    public function descendant($path)
    {
        return new Descendant($path);
    }

    public function like($field, $value)
    {
        return new Comparison($field, Comparison::LIKE, $value);
    }

    public function eqNodeName($value)
    {
        return new NodeLocalName(Comparison::EQ, $value);
    }

    public function likeNodeName($value)
    {
        return new NodeLocalName(Comparison::LIKE, $value);
    }

    public function textSearch($field, $search)
    {
        return new TextSearch($field, $search);
    }

    public function eqPath($path)
    {
        return new SameNode($path);
    }
}
