<?php

namespace Doctrine\ODM\PHPCR\Query\Expression;

use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;

class TextSearch implements Expression
{
    private string $field;
    private string $search;

    public function __construct(string $field, string $search)
    {
        $this->field = $field;
        $this->search = $search;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getSearch(): string
    {
        return $this->search;
    }

    public function visit(ExpressionVisitor $visitor)
    {
    }
}
