<?php

namespace Doctrine\ODM\PHPCR\Query\Expression;

use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;

class TextSearch implements Expression
{
    protected $field;
    protected $search;

    public function __construct($field, $search)
    {
        $this->field = $field;
        $this->search = $search;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getSearch()
    {
        return $this->search;
    }

    public function visit(ExpressionVisitor $visitor)
    {
    }
}
