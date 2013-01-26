<?php

namespace Doctrine\ODM\PHPCR\Query;

use Doctrine\Common\Collections\ExpressionBuilder as BaseExpressionBuilder;
use Doctrine\ODM\PHPCR\Query\Expression\Descendant;
use Doctrine\ODM\PHPCR\Query\Expression\Comparison;
use Doctrine\ODM\PHPCR\Query\Expression\TextSearch;

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

    public function textSearch($field, $search)
    {
        return new TextSearch($field, $search);
    }
}
